# shellcheck shell=bash

# shellcheck source=./bin/lib.sh
. "${BIN_DIR:-./bin}"/lib.sh

readonly MIN_DOCKER_VERSION=17.09
readonly MIN_PHP_VERSION=7.2

setError() {
    local ns=$1
    local msg=$2

    ns=$(strToUpper "$ns")

    local var=INSTALL_ERRORS_${ns}
    declare -g "$var"
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
    for e in ${!INSTALL_ERRORS_*}; do
        local ns=${e#INSTALL_ERRORS_}
        local name=INSTALL_ERRORS_${ns}
        local var=${!name}
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
    command -v "$name" &> /dev/null
    [[ $? == 0 ]]
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

    local version
    version=$("${cmd[@]}")
    local status=$?

    local strCmd=$(quoteCmd "${cmd[@]}")
    if [[ $status -ne 0 ]]; then
        log error "Failed to check the installed version of $name" \
            "Command \`$strCmd\` returned $status"
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
    [[ $OSTYPE =~ linux || $(uname) =~ Darwin ]] || {
        setError OS "Docker installation is only supported on Linux & Mac"
        return 1
    }
    return 0
}

selectInstallMethod() {
    declare -g INSTALL_METHOD=${INSTALL_METHOD:-}
    if [[ -n $INSTALL_METHOD ]]; then
        debug "INSTALL_METHOD has been overriden to: $INSTALL_METHOD"
        return
    fi

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

afterInstall() {
    local path=$1
    local bindir=${path%/}/bin
    local updated="0"

    local pat=":?$bindir:?"
    if [[ $PATH =~ $pat ]]; then
        log info "Looks like $bindir is already in your \$PATH"
    else
        log warn "$bindir is not in your \$PATH" "> $PATH"
        if ask "Would you like us to update your .bashrc/.zshrc files?"; then
            local files=(
                "$HOME/.bashrc"
                "$HOME/.zshrc"
            )
            local updated=0
            for rc in "${files[@]}"; do
                updateBashProfile "$bindir" "$rc"
                updated=$((updated + 1))
            done
            if (( updated == 0 )); then
                log warn \
                    "Could not find any rc files. You should create one with:" \
                    "echo \"PATH=${bindir}:\$PATH\" >> \"$HOME/.bashrc\""
            fi
        fi
    fi

    echo ""

    setUserOptions
    colorText "green" "NorthStack client successfully installed at $path/bin/northstack ✅"
}

updateBashProfile() {
    local bindir=$1
    local bashFile=$2

    local checksumPre=$(md5sum "$bashFile" | awk '{print $1}')
    local new=$(mktemp)

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
        local total=$(wc -l "$bashFile" | awk '{print $1}')
        local lastN=$((total - endLine + 1))
        tail -n "$lastN" "$bashFile" >> "$new"
    else
        echo '# NorthStack END' >> "$new"
    fi

    local checksumPost=$(md5sum "$bashFile" | awk '{print $1}')

    if [[ $checksumPre != "$checksumPost" ]]; then
        log error "RC file ($bashFile) changed while we were updating it"
        return 1
    fi

    if ! diff "$bashFile" "$new"; then
        cat "$new" > "$bashFile"
        log info "Updated: $bashFile"
    else
        debug "No changes detected--moving on"
    fi
    rmFile "$new"
}

checkPathPermissions() {
    local prefix=$1
    log info "Checking installation paths for writeability"

    local failed=0

    local toCheck=(
        "$prefix/bin/northstack"
        "$prefix/northstack"
    )

    for p in "${toCheck[@]}"; do
        local parent=$(parentDir "$p")
        if [[ -e $p && ! -w $p ]] || [[ ! -w $parent ]]; then
            log error \
                "Could not confirm writeability for the following location:" \
                "  -   Path: $p" \
                "  - Parent: $parent"
                    failed=1
        fi
    done

    return "$failed"
}

doNativeInstall() {
    local context=$1

    log info "Installing natively"

    installComposerDeps "$context"

    copyTree "$context" "$INSTALL_PATH/northstack"
    lnS "$INSTALL_PATH/northstack/bin/northstack" "${INSTALL_PATH}/bin/northstack"

    afterInstall "$INSTALL_PATH"
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
    local isDev=$2

    log info "Installing with docker"

    checkDocker
    [[ $isDev == 1 ]] && installComposerDeps "$context"
    buildDockerImage "$context"

    local wrapperFile=$(mktemp)

    # shellcheck disable=SC2064
    trap "rm '$wrapperFile'" EXIT

    buildWrapper "$wrapperFile" "$context" "$isDev"

    copyFile "$wrapperFile" "${INSTALL_PATH}/bin/northstack"
    copyTree "${context}/docker" "${INSTALL_PATH}/northstack/docker"

    afterInstall "$INSTALL_PATH"
}

setUserOptions() {
    if [[ -f $HOME/.northstack-settings.json ]]; then
        log     info "Previous settings found..."
        return
    fi

    local appDir="$HOME/northstack/apps"

    echo "Use the default recommended directory to store your apps?"
    read -r -p "($HOME/northstack/apps) (Y/n)? " choice

    case "$choice" in
        y | Y)
            colorText  grey "Default directory selected"
            ;;
        n | N)
            echo "Please enter the full path of the directory where you'd like to store your local NorthStack apps"
            read -r customAppDir
            appDir=${customAppDir/#~/$HOME}
            ;;
        *)
            colorText yellow "Default choice selected, continuing with default apps directory"
            ;;
    esac

    colorText grey "Selected directory: $appDir"

    # Check to see if the dir already exists -- if not, try to create it
    if [ -d "$appDir" ]; then
        colorText grey "Chosen directory found, continuing"
    else
        colorText grey "Chosen directory not found, attempting to create it"
        if ! mkdirP "$appDir"; then
            log error "There was an issue creating the directory $appDir, please manually create the directory and try again."
        else
            colorText "green" "NorthStack apps dir successfully created at $appDir"
        fi
    fi

    # Save that directory path to a new user settings file
    updateUserSettings "$appDir"
}

updateUserSettings() {
    local appDir=$1
    local settingsDir="$HOME"
    local settingsFile=".northstack-settings.json"
    local fullPath="$settingsDir/$settingsFile"

    colorText grey "Checking to make sure there's not an existing settings file (if there is, a backup will be saved)."

    if [[ -e $fullPath ]]; then
        colorText yellow "Existing settings file found, backing it up before we proceed."
        local backupDate=$(date '+%Y-%m-%d_%H%M')
        local backupFullPath="$settingsDir/.northstack-settings--backup-$backupDate.json"
        copyFile "$fullPath" "$backupFullPath"
        rmFile "$fullPath"
    fi

    local json="{\"local_apps_dir\":\"$appDir\"}"

    echo "$json" > "$fullPath"

    if [[ ! -w $fullPath ]]; then
        log error "Looks like $fullPath is not writeable. Please save the following contents to it:"
        log error "$json"
        showErrors
    fi

    colorText grey "User settings update complete"
}

complain() {
    log error "Could not verify the minimum installation requirements for your system"
    showErrors
    exit 1
}

install() {
    local context=$1
    local isDev=${2:-0}

    selectInstallMethod
    setInstallPath
    checkPathPermissions "$INSTALL_PATH"
    case $INSTALL_METHOD in
        native)
            doNativeInstall "$context"
            ;;
        docker)
            doDockerInstall "$context" "$isDev"
            ;;
        *)
            complain
            ;;
    esac
}
