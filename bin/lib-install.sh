# shellcheck shell=bash

# shellcheck source=./bin/lib.sh
. "${BIN_DIR:-./bin}"/lib.sh

readonly MIN_DOCKER_VERSION=17.09
readonly MIN_PHP_VERSION=7.2

declare INSTALL_METHOD
declare INSTALL_PREFIX
declare INSTALL_APPDIR
declare INSTALL_DEV_MODE

setError() {
    local ns=$1
    local msg=$2
    if [[ $msg == "-" ]]; then
        msg=$(< /dev/stdin)
    fi

    ns=$(strToUpper "$ns")

    local var=INSTALL_ERRORS_${ns}
    local val=${!var:=}
    local ifs=$IFS
    IFS=$'\n'
    for current in $val; do
        if [[ $current == "$msg" ]]; then
            # duplicate
            return
        fi
    done
    IFS=$ifs
    [[ -n $val ]] && val="${val}\n"
    val=${val}${msg}
    printf -v "$var" "$val"
}

showErrors() {
    local errors=${!INSTALL_ERRORS_*}

    if [[ -z $errors ]]; then
        log info "No errors detected during installation pre-flight checks"
        return 0
    fi

    log error "Could not verify the minimum installation requirements for your system"

    for e in ${!INSTALL_ERRORS_*}; do
        local ns=${e#INSTALL_ERRORS_}
        local name=INSTALL_ERRORS_${ns}
        local var=${!name}
        log error "$ns errors:"
        local ifs=$IFS
        IFS=$'\n'
        for err in $var; do
            log error "  * $err"
        done
        IFS=$ifs
    done

    return 1
}

getVersion() {
    local name=$1
    local dest=$2

    local cmd
    case $name in
        php)
            cmd=(php -r 'echo phpversion();')
            ;;
        docker)
            cmd=(docker info --format '{{ .ServerVersion }}')
            ;;
        *)
            log error "Can't get version for $name"
            return 1
            ;;
    esac

    local version; version=$("${cmd[@]}" 2>&1)
    local status=$?

    local strCmd; strCmd=$(quoteCmd "${cmd[@]}")
    if [[ $status -ne 0 ]]; then
        log error "Failed to check the installed version of $name" \
            "Command \`$strCmd\` returned $status" \
            "Output: '$version'"
        version=0
    fi
    printf -v "$dest" "$version"
    return $status
}

versionCompare() {
    local left=${1:-0}
    local right=${2:-0}

    [[ $left == "$right" ]] && return 0

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

    l_num=${l_num##0}
    l_num=${l_num:-0}
    r_num=${r_num##0}
    r_num=${r_num:-0}

    if ((l_num > r_num)); then
        return 0
    elif ((l_num >= r_num))   && [[ $l_extra == "$r_extra" || $l_extra > $r_extra ]]; then
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

    # shellcheck disable=SC2153
    versionCompare "$VERSION" "$min" && return 0

    setError "$name" "Minimum version requirement for $name not met (installed: $VERSION, required: $min)"
    return 1
}

nativeInstallOK() {
    if ! iHave php; then
        setError php "No php binary preset in \$PATH - is PHP installed?"
        return 1
    fi
    checkVersion php "$MIN_PHP_VERSION" || return 1

    {
        checkDocker \
            && checkVersion docker "$MIN_DOCKER_VERSION"
    } || return 1
    return 0
}

validateOptions() {

    INSTALL_METHOD=${INSTALL_METHOD:-auto}
    log info "Checking system requirements for install method: $INSTALL_METHOD"
    case $INSTALL_METHOD in
        docker)
            dockerInstallOK || true
            ;;
        native)
            nativeInstallOK || true
            ;;
        auto)
            selectInstallMethod
            ;;
        *)
            setError opts "Unknown install method ($INSTALL_METHOD). Please chose one of docker/native/auto"
            ;;
    esac

    INSTALL_PREFIX=${INSTALL_PREFIX:-$HOME/.local}
    INSTALL_APPDIR=${INSTALL_APPDIR:-$HOME/northstack/apps}
    checkPathPermissions
}

dockerInstallOK() {
    if ! iHave docker; then
        setError docker "No docker executable found. Is docker installed?"
        return 1
    fi

    if ! docker info &> /dev/null; then
        setError docker "Running \`docker info\` failed. Is the docker daemon running?"
        return 1
    fi

    if ! checkVersion docker "$MIN_DOCKER_VERSION"; then
        return 1
    fi

    if ! [[ $OSTYPE =~ linux || $OSTYPE =~ darwin ]]; then
        setError docker "Docker installation is only supported on Linux & Mac"
        return 1
    fi
}

selectInstallMethod() {
    if dockerInstallOK; then
        INSTALL_METHOD=docker
        return
    fi

    if nativeInstallOK; then
        INSTALL_METHOD=native
        return
    fi

    INSTALL_METHOD=none
}

createRcFile() {
    local -r bindir=$1

    local toCreate=()
    if iHave zsh; then
        toCreate+=("$HOME/.zshrc")
    fi

    if iHave bash; then
        toCreate+=("$HOME/.bashrc")
        toCreate+=("$HOME/.bash_profile")
    fi

    if [[ ${#toCreate[@]} -gt 0 ]]; then
        log warn "Couldn't find any rc files; would you like us to create them for you?"
        local list; list=$(printf -- "- %s\n" "${toCreate[@]}")
        if ask "The installer wants to create the following files:\n${list}"; then
            for rc in "${toCreate[@]}"; do
                touch "$rc"
                updateBashProfile "$bindir" "$rc" || \
                    log warn "Updating $rc failed"
            done
        fi
    fi
}

afterInstall() {
    local path=$1
    local bindir=${path%/}/bin
    local updated="0"

    local pat=":?$bindir:?"
    if [[ $PATH =~ $pat ]]; then
        log info "Looks like $bindir is already in your \$PATH"
    else
        log warn "$bindir is not in your \$PATH" "> $PATH"

        local files=(
            "$HOME/.bashrc"
            "$HOME/.bash_profile"
            "$HOME/.zshrc"
        )

        local found=()
        for rc in "${files[@]}"; do

            if [[ ! -f "$rc" ]]; then
                continue
            fi

            found+=("$rc")
        done

        if [[ ${#found[@]} -eq 0 ]]; then
            createRcFile "$bindir"
        else
            if ask "Would you like us to update your .bashrc/.zshrc files?"; then
                local updated=0
                for rc in "${found[@]}"; do
                    if updateBashProfile "$bindir" "$rc"; then
                        updated=$((updated + 1))
                    fi
                done
                if (( updated == 0 )); then
                    log warn "Failed to update rc files."
                fi
            fi
        fi
    fi

    setUserOptions
    log info "NorthStack client successfully installed at $path/bin/northstack ✅"
}

checksumFile() {
    local -r file=$1
    if iHave md5sum; then
        md5sum "$file" | awk '{print $1}'
    elif iHave md5; then
        md5 -r "$file" | awk '{print $1}'
    else
        log error "Cannot checksum $file - No md5/md5sum utility found!"
        return 1
    fi
}

updateBashProfile() {
    local bindir=$1
    local bashFile=$2

    if [[ ! -e $bashFile ]]; then
        debug "file ($bashFile) not found--skipping"
        return 1
    elif [[ ! -f $bashFile ]]; then
        debug "file ($bashFile) exists but isn't a regular file--skipping"
        return 1
    elif [[ ! -w $bashFile ]]; then
        debug "file ($bashFile) exists but is not writable--skipping"
        return 1
    fi

    local checksumPre; checksumPre=$(checksumFile "$bashFile")
    local new; new=$(mktemp)

    local startLine
    startLine=$(sed -n -e '/^# NorthStack START$/=' < "$bashFile")

    if [[ -n $startLine ]]; then
        head -n "$startLine" "$bashFile" > "$new"
    else
        cat "$bashFile" > "$new"
        echo '# NorthStack START' >> "$new"
    fi

    echo "PATH=${bindir}:\$PATH" >> "$new"

    local endLine
    endLine=$(sed -n -e '/^# NorthStack END$/=' < "$bashFile")

    if [[ -n $endLine ]]; then
        local total; total=$(wc -l "$bashFile" | awk '{print $1}')
        local lastN; lastN=$((total - endLine + 1))
        tail -n "$lastN" "$bashFile" >> "$new"
    else
        echo '# NorthStack END' >> "$new"
    fi

    local checksumPost; checksumPost=$(checksumFile "$bashFile")

    if [[ $checksumPre != "$checksumPost" ]]; then
        log error "RC file ($bashFile) changed while we were updating it"
        return 1
    fi

    if ! diff "$bashFile" "$new"; then
        log info "Updating: $bashFile"
        cat "$new" > "$bashFile"
        log warn "Updated rc file: '$bashFile'" \
            "You must launch a new shell or reload the rc file (\`source \"$bashFile\"\`) to see the changes in your \$PATH"
    else
        debug "No changes detected--moving on"
    fi
    rmFile "$new"
}

readableStat() {
    local p=$1
    stat -c "File: %n\nType: %F\nMode: (%a / %A)\nOwner: (%u / %U)\nGroup: (%g / %G)" \
        "$p" \
    || printf "ERROR: failed to call stat on '%s' failed" "$p"
}

checkPathPermissions() {
    local prefix=$INSTALL_PREFIX
    local appdir=$INSTALL_APPDIR
    log info "Checking installation paths for writeability"

    local toCheck=(
        "binary:$prefix/bin/northstack"
        "library:$prefix/northstack"
        "appdir:$appdir"
    )

    for item in "${toCheck[@]}"; do
        local name=${item%%:*}
        local path=${item#*:}
        local parent; parent=$(parentDir "$path")
        if [[ -e $path && ! -w $path ]]; then
            setError "${name}_path" "$name path ($path) exists but is not writable."
            setError "${name}_path" "$(readableStat "$path")"
        elif [[ ! -w $parent ]]; then
            setError "${name}_path" "$name path ($path) does not exist, and its parent ($parent) is not writable."
            setError "${name}_path" "$(readableStat "$parent")"
        fi
    done
}

doNativeInstall() {
    local context=$1

    log info "Installing natively"

    installComposerDeps "$context"

    shopt -s dotglob nullglob
    for p in "$context"/*; do
        local name; name=$(basename "$p")
        if [[ "$p" =~ (\.(git|buildkite|github|tmp)$) ]]; then
            continue
        fi
        if [[ -d "$p" ]]; then
            copyTree "$p" "$INSTALL_PREFIX/northstack/$name"
        elif [[ -f "$p" ]]; then
            copyFile "$p" "$INSTALL_PREFIX/northstack/$name"
        else
            log warn "Unknown file type: $p"
        fi
    done

    lnS "$INSTALL_PREFIX/northstack/bin/northstack" "${INSTALL_PREFIX}/bin/northstack"

    afterInstall "$INSTALL_PREFIX"
}

buildWrapper() {
    local out=$1
    local base=$2
    local dev=${3:-0}

    cat << EOF > "$out"
#!/usr/bin/env bash

set -eu

DEV_MODE=$dev
DEV_SOURCE=$base

$(< "$base"/bin/wrapper-lib.sh)

$(< "$base"/bin/wrapper-main.sh)

main "\$@"
EOF
    chmod +x "$out"
}

doDockerInstall() {
    local context=$1
    local isDev=${INSTALL_DEV_MODE:-0}

    log info "Installing with docker"

    checkDocker
    [[ $isDev == 1 ]] && installComposerDeps "$context"
    buildDockerImage "$context"

    local wrapperFile; wrapperFile=$(mktemp)

    # shellcheck disable=SC2064
    #trap "rm -f '$wrapperFile'" EXIT

    buildWrapper "$wrapperFile" "$context" "$isDev"

    copyFile "$wrapperFile" "${INSTALL_PREFIX}/bin/northstack"
    copyTree "${context}/docker" "${INSTALL_PREFIX}/northstack/docker"

    afterInstall "$INSTALL_PREFIX"
}

setUserOptions() {
    local path=$HOME/.northstack-settings.json
    if [[ -e $path ]]; then
        log info "Existing ~/.northstack-settings.json found"
        return
    fi

    log info "Creating ~/.northstack-settings.json"

    mkdirP "$INSTALL_APPDIR"

    local json="{\"local_apps_dir\":\"$INSTALL_APPDIR\"}"
    echo "$json" > "$path"

    log info "User settings update complete"
}

install() {
    local context=$1

    validateOptions
    showErrors
    case $INSTALL_METHOD in
        native)
            doNativeInstall "$context"
            ;;
        docker)
            doDockerInstall "$context"
            ;;
        *)
            log error "Unknown installation method: $INSTALL_METHOD"
            exit 1
            ;;
    esac
}
