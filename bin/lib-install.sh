. ./bin/lib.sh

readonly MIN_DOCKER_VERSION=17.09
readonly MIN_PHP_VERSION=7.2

declare -Ag INSTALL_ERRORS

setError() {
    declare -u ns=$1
    local msg=$2

    local val=${INSTALL_ERRORS[$ns]:-}
    [[ ! -z $val ]] && val="${val}\n"
    val=${val}${msg}
    INSTALL_ERRORS[$ns]=$val
}

showErrors() {
    for ns in ${!INSTALL_ERRORS[@]}; do
        local var=${INSTALL_ERRORS[$ns]};
        printf "$ns errors:\n"
        local ifs=$IFS
        IFS=$'\n'
        for err in $var; do
            printf '  * %s\n' "$err" 1>&2
        done
        IFS=$ifs
    done
}

iHave() {
    local name=$1
    which "$name" &> /dev/null
    return $?
}

getVersion() {
    local name=$1
    local dest=$2
    local version
    local status

    case $name in
        php)
            version=$(php -r 'echo phpversion();')
            status=$?;;

        docker)
            version=$(docker info --format '{{ .ServerVersion }}')
            status=$?;;
        *)
            log error "Can't get version for $name"
            return 1
    esac

    printf -v "$dest" "$version"
    return "$status"
}

versionCompare() {
    local left=${1:-0}
    local right=${2:-0}

    [[ $left == $right ]] && return 0

    if [[ $left =~ ^[[:digit:]]$ ]] && [[ $right =~ ^[[:digit:]]$ ]]; then
        [[ $left -ge $right ]] && return 0
        return 1
    fi

    local l_part
    local r_part

    if [[ $left =~ [.] ]]; then
        l_part=${left%%.*}
        left=${left#$l_part.}
    else
        l_part=$left
        left=
    fi

    if [[ $right =~ [.] ]]; then
        r_part=${right%%.*}
        right=${right#$r_part.}
    else
        r_part=$right
        right=
    fi

    local l_num=$l_part
    local r_num=$r_part

    while [[ $l_part =~ ^[[:digit:]] ]]; do
        l_part=${l_part:1}
    done
    local l_extra=$l_part
    l_num=${l_num%$l_extra}

    while [[ $r_part =~ ^[[:digit:]] ]]; do
        r_part=${r_part:1}
    done
    local r_extra=$r_part
    r_num=${r_num%$r_extra}

    l_num=${l_num##0}; l_num=${l_num:-0}
    r_num=${r_num##0}; r_num=${r_num:-0}

    if (( l_num > r_num )); then
        return 0
    elif (( l_num >= r_num )) && [[ $l_extra == $r_extra || $l_extra > $r_extra ]]; then
        versionCompare "$left" "$right" && return 0
    fi

    return 1
}

checkVersion() {
    local name=$1
    local min=$2

    getVersion "$name" VERSION
    if [[ $? -ne 0 ]]; then
        setError "$name" "Couldn't check the installed version of $name"
        return 1
    fi

    versionCompare "$VERSION" "$min" && return 0

    setError "$name" "Minimum version requirement for $name not met (installed: $VERSION, required: $min)"
    return 1
}

nativeInstallOK() {
    {
        iHave php \
        && checkVersion php "$MIN_PHP_VERSION"
    } || return 1

    {
        iHave docker \
        && checkVersion docker "$MIN_DOCKER_VERSION"
    } || return 1
    return 0
}

dockerInstallOK() {
    {
        iHave docker \
        && checkVersion docker "$MIN_DOCKER_VERSION"
    } || return 1
    [[ $OSTYPE =~ linux ]] || {
        setError OS "Docker installation is only supported on Linux"
        return 1
    }
    return 0
}

selectInstallMethod() {
    nativeInstallOK && {
        printf -v INSTALL_METHOD "native"
        return 0
    }

    dockerInstallOK && {
        printf -v INSTALL_METHOD "docker"
        return 0
    }
    printf -v INSTALL_METHOD "none"
}

doNativeInstall() {
    local cxt=$1

    log info "Installing natively"

    local install_path=$(setInstallPrefix)
    installComposerDeps "$ctx"

    local dest="${install_path}/northstack"

    mkdirP "$dest"
    copyFiles "$ctx" "$dest"
    lnS "$dest/bin/northstack" "${install_path}/bin/northstack"
}

doDockerInstall() {
    local ctx=$1
    local isDev=$2

    log info "Installing with docker"

    checkDocker
    [[ $isDev == 1 ]] && installComposerDeps "$ctx"
    buildDockerImage "$ctx"

    local wrapperFile=$(mktemp)

    trap "rm '$wrapperFile'" EXIT

    local install_path=$(setInstallPrefix)

    "$ctx"/bin/build-wrapper.sh "$wrapperFile" "$BASE" "$isDev"

    copyFiles "$wrapperFile" "${install_path}/bin/northstack"
    copyFiles "${ctx}/docker" "${install_path}/lib/northstack/docker"
}

complain() {
    log error "Could not verify the minimum installation requirements for your system"
    showErrors
    exit 1
}

install() {
    local ctx=$1
    local isDev=${2:-0}

    selectInstallMethod
    case $INSTALL_METHOD in
        native)
            doNativeInstall "$ctx";;
        docker)
            doDockerInstall "$ctx" "$isDev";;
        *)
            complain;;
    esac

}
