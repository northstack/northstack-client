# shellcheck shell=bash
. ${BIN_DIR:-./bin}/lib.sh

readonly MIN_DOCKER_VERSION=17.09
readonly MIN_PHP_VERSION=7.2

setError() {
    local ns=$1
    local msg=$2

    ns=$(strToUpper "$ns")

    local var=INSTALL_ERRORS_${ns}
    local val=${!var:=}
    local ifs=$IFS
    IFS=$'\n'
    for current in $val; do
        if [[ $current == $msg ]]; then
            # duplicate
            return
        fi
    done
    IFS=$ifs
    [[ ! -z $val ]] && val="${val}\n"
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
    which "$name" &> /dev/null
    return $?
}

getVersion() {
    local name=$1
    local dest=$2

    local cmd
    case $name in
        php)
            cmd=(php -r 'echo phpversion();');;
        docker)
            cmd=(docker info --format '{{ .ServerVersion }}');;
        *)
            log error "Can't get version for $name"
            return 1
    esac

    local version=$("${cmd[@]}")
    local status=$?

    local strCmd=${cmd[@]}
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
    [[ $OSTYPE =~ linux || `uname` =~ Darwin ]] || {
        setError OS "Docker installation is only supported on Linux & Mac"
        return 1
    }
    return 0
}

selectInstallMethod() {
    dockerInstallOK && {
        printf -v INSTALL_METHOD "docker"
        return 0
    }

    nativeInstallOK && {
        printf -v INSTALL_METHOD "native"
        return 0
    }

    printf -v INSTALL_METHOD "none"
}

afterInstall() {
    local path=$1
    local bindir=$path/bin
    local updated="0"

    # We're just going to attempt to update all of the potential bash profiles that might be used. Everyone is different!
    log info "Attempting to update all possible bash/zsh profiles -- sometimes we've just gotta do that."
    if [ -w $HOME/.bash_profile ]
    then
        updateBashProfile $bindir $HOME/.bash_profile
        updated=1
    fi

    if [ -w $HOME/.bashrc ]
    then
        updateBashProfile $bindir $HOME/.bashrc
        updated=1
    fi

    if [ -w $HOME/.zshrc ]
    then
        updateBashProfile $bindir $HOME/.zshrc
        updated=1
    fi

    if [ $updated == "0" ]
    then
        if [ -f $HOME/.bash_profile ]
        then
            log error "Please make ~/.bash_profile writable and re-run install.sh"
            exit 1
        fi

        log warn "Could not find any bash or zsh profiles to update. Creating ~/.bash_profile"
        touch ~HOME/.bash_profile
        afterInstall $1
    fi

    echo ""

    setUserOptions
    colorText "green" "NorthStack client successfully installed at $path/bin/northstack ✅"
}

updateBashProfile() {
    return
    local bindir=$1
    local bashFile=$2

    if grep --quiet $bindir $bashFile; then
        # The user still needs to refresh their terminal window to use the new source
        colorText grey "NorthStack path found in ${bashFile}, continuing without update."
    else
        colorText grey "NorthStack path not found in ${bashFile}, verifying that the file is writeable."
        if [ -w $bashFile ]; then
            colorText grey "${bashFile} is writeable."
            # Add to the bashrc file so the command can be run
            echo "# NorthStack path: " >> $bashFile
            echo "export PATH=${bindir}:\$PATH" >> $bashFile

            # The user still needs to refresh their terminal window to use the new source
            colorText "white" "                                                                 " true "red"
            log warn "**** New path added to your bash profile, please start a new terminal session. ****"
            colorText "white" "                                                                 " true "red"
        else
            log error "Looks like ${bashFile} is not writeable. Please add the following to it and then open a new terminal session: "
            log error "export PATH=${bindir}:\$PATH"
            showErrors
        fi
    fi
}

doNativeInstall() {
    local context=$1

    log info "Installing natively"

    local install_path=$(setInstallPrefix)
    installComposerDeps "$context"

    debug "Install path: ${install_path}"
    local dest="${install_path}/northstack"

    mkdirP "$dest"
    copyFiles "$context" "$dest"
    lnS "$dest/bin/northstack" "${install_path}/bin/northstack"

    afterInstall "$install_path"
}

doDockerInstall() {
    local context=$1
    local isDev=$2

    log info "Installing with docker"

    checkDocker
    [[ $isDev == 1 ]] && installComposerDeps "$context"
    buildDockerImage "$context"

    local wrapperFile=$(mktemp)

    trap "rm '$wrapperFile'" EXIT

    local install_path=$(setInstallPrefix)

    "$context"/bin/build-wrapper.sh "$wrapperFile" "$BASE" "$isDev"

    copyFiles "$wrapperFile" "${install_path}/bin/northstack"
    copyFiles "${context}/docker" "${install_path}/northstack/docker"

    afterInstall "$install_path"
}

setUserOptions() {
    if [ -f $HOME/.northstack-settings.json ]
        then
            log info "Previous settings found..."
        return
    fi

    local appDir="$HOME/northstack/apps"

    echo "Use the default recommended directory to store your apps?"
    read -p "($HOME/northstack/apps) (Y/n)? " choice

    case "$choice" in
        y|Y )
             colorText grey "Default directory selected";;
        n|N )
            echo "Please enter the full path of the directory where you'd like to store your local NorthStack apps"
            read customAppDir
            appDir=${customAppDir/#~/$HOME};;
        * )
            colorText yellow "Default choice selected, continuing with default apps directory";;
    esac

    colorText grey "Selected directory: $appDir"

    # Check to see if the dir already exists -- if not, try to create it
    if [ -d "$appDir" ]
    then
        colorText grey "Chosen directory found, continuing"
    else
        colorText grey "Chosen directory not found, attempting to create it"
        if ! mkdirP "$appDir";
        then
            log error "There was an issue creating the directory $appDir, please manually create the directory and try again."
        else
            colorText "green" "NorthStack apps dir successfully created at $appDir"
        fi
    fi

    # Save that directory path to a new user settings file
    updateUserSettings "$appDir"
}

updateUserSettings() {
    local settingsDir="$HOME"
    local settingsFile=".northstack-settings.json"
    local fullPath="$settingsDir/$settingsFile"

    colorText grey "Checking to make sure there's not an existing settings file (if there is, a backup will be saved).";

    if [[ -e $fullPath ]];
    then
        colorText yellow "Existing settings file found, backing it up before we proceed."
        local backupDate=$(date '+%Y-%m-%d_%H%M')
        local backupFullPath="$settingsDir/.northstack-settings--backup-$backupDate.json"
        cp $fullPath $backupFullPath
        rm $fullPath
    fi

    local json="{\"local_apps_dir\":\"$1\"}"

    echo "$json"> $fullPath

    if [ ! -w $fullPath ]; then
        chmod
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
    case $INSTALL_METHOD in
        native)
            doNativeInstall "$context";;
        docker)
            doDockerInstall "$context" "$isDev";;
        *)
            complain;;
    esac
}
