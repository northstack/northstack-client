# shellcheck shell=bash
strToUpper() {
    tr '[:lower:]' '[:upper:]' <<< "$@"
}

strToLower() {
    tr '[:upper:]' '[:lower:]' <<< "$@"
}

log() {
    local level=$1
    level=$(strToLower "$level")

    local red=$'\e[1;91m'
    local green=$'\e[1;32m'
    local yellow=$'\e[1;33m'
    local blue=$'\e[1;34m'
    local magenta=$'\e[1;35m'
    local cyan=$'\e[1;36m'
    local white=$'\e[1;37m'
    local end=$'\e[0m'

    local level_color

    case $level in
        info)
            level_color=$green
            shift
            ;;
        debug)
            level_color=$blue
            shift
            ;;
        warn)
            level_color=$magenta
            shift
            ;;
        error)
            level_color=$red
            shift
            ;;
        *)
            level_color=$green
            level='info'
            ;;
    esac

    local ts=$(date '+%Y-%m-%d %H:%M:%S')
    local template="[$ts] [$level] %s\n"
    if [[ -t 2 ]]; then
        template="[${white}${ts}${end}] [${level_color}${level}${end}] %s\n"
    fi

    case "$@" in
        -)
            while read -rs line; do
                printf "$template" "$line" > /dev/stderr
            done
            ;;
        *)
            printf "$template" "$@" > /dev/stderr
            ;;
    esac
}

getCwd() {
    (cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
}

debug() {
    local DEBUG=${DEBUG:-0}
    if [[ $DEBUG == 1 ]]; then
        log "debug" "$@"
    fi
}

setInstallPrefix() {
    local default=$HOME/.local

    declare -g INSTALL_PATH=${INSTALL_PATH:-}
    if [[ -z $INSTALL_PATH ]]; then
        log "Using default install prefix ($default)"
        log "You can change this behavior by setting the \$INSTALL_PATH environment variable"
        INSTALL_PATH=$default
    else
        log "Using install path: $INSTALL_PATH"
    fi
    INSTALL_PATH=${INSTALL_PATH%*/}
}

getInstallPrefix() {
    local binDir="$(getCwd)"
    printf "$(dirname "$binDir")"
}

shellIsInteractive() {
    [[ -t 0 ]]
    #[[ -t 0 || -p /dev/stdin ]]
}

ask() {
    local question=$1
    local default=${2:-no}

    if ! shellIsInteractive; then
        if [[ -z ${ASK_FORCE_INTERACTIVE:-} ]]; then
            debug "This is not an interactive shell--no sense in asking"
            return 2
        fi
    fi

    echo -e "$question"
    read -r -p "Enter yes/no (default = $default): " answer

    [[ $answer == "yes" ]] || [[ $answer != "no" && $default == "yes" ]]
}

askForSudo() {
    [[ ${NORTHSTACK_ALLOW_SUDO:-0} == 1 ]] && return 0
    local question="Asking permission to run the following command with sudo:"
    question="${question}\n$(quoteCmd "$@")"
    question="${question}\nYou can disable this check by setting NORTHSTACK_ALLOW_SUDO=1"

    ask "$question"
}

copyFile() {
    local src=${1:?src path required}
    local dest=${2:?dest path required}

    assertSafePath "$dest" || return 1

    if [[ ! -f $src ]]; then
        log error "File $src does not exist or is not a regular file"
        return 1
    fi

    if [[ -e $dest && ! -f $dest ]]; then
        log error "Destination exists and is not a regular file"
        return 1
    fi

    local parent=$(dirname "$dest")
    mkdirP "$parent" || return 1

    set -- cp -va "$src" "$dest"

    if [[ ! -w $parent ]] || [[ -f $dest && ! -w $dest ]]; then
        askForSudo "$@" || return 1
        set -- sudo "$@"
    fi

    debugCmd "$@"
}

copyTree() {
    local src=${1:?src path required}
    local dest=${2:?dest path required}

    assertSafePath "$dest" || return 1

    if [[ ! -d $src ]]; then
        log error "Source path ($src) does not exist or is not a directory"
        return 1
    fi

    if [[ -d $dest ]]; then
        log warn "Destination directory already exists--removing"
        rmDir "$dest"
    elif [[ -e $dest ]]; then
        local filetype=$(stat -c '%F' "$dest")
        log error "Destination exists but is not a directory: $filetype"
        return 1
    fi

    mkdirP "$dest"

    src=${src%/}/.
    dest=${dest%/}

    debugCmd cp -av "$src" "$dest"
}

parentDir() {
    local dir=$1
    while [[ ! -d $dir ]]; do
        dir=$(dirname "$dir")
    done
    echo -e "$dir"
}

userOwnership() {
    echo -n "$(id -u -n):$(id -g -n)"
}

assertSafePath() {
    local path=${1:?path required}
    path=${path%/}

    local safeFiles=(
        "$HOME/.northstack-settings.json"
    )

    local safeDirs=(
        /tmp
    )

    # if the user has a custom tmpdir defined let's add that the safe paths
    if [[ -n ${TMPDIR:-} ]]; then
        safeDirs+=("${TMPDIR%/}")
    fi

    if [[ -n ${INSTALL_PATH} ]]; then
        safeDirs+=("$INSTALL_PATH"/northstack)
        safeFiles+=("$INSTALL_PATH"/bin/northstack)
    fi

    debug "safe files:" "${safeFiles[@]}"
    debug "safe dirs:" "${safeDirs[@]}"

    local file
    for file in "${safeFiles[@]}"; do
        if [[ $path == "$file" ]]; then
            return 0
        fi
    done

    local dir
    for dir in "${safeDirs[@]}"; do
        if [[ $path == "$dir" || $path == $dir/* ]]; then
            return 0
        fi
    done

    log error "[${FUNCNAME[1]}] Refusing to act on unsafe path: $path"
    return 1
}

mkdirP() {
    local dir=$1
    [[ -d $dir ]] && return 0
    assertSafePath "$dir" || return 1
    local parent=$(parentDir "$dir")
    if [[ -w $parent ]]; then
        debugCmd mkdir -pv "$dir"
    else
        log "warn" "Parent directory $parent is not writeable by your shell user. Using sudo to create $dir"
        chownTo=$(userOwnership)
        askForSudo mkdir -pv "$dir" \; chown "$chownTo" "$dir" || return 1
        debugCmd sudo mkdir -pv "$dir"
        debugCmd sudo chown "$chownTo" "$dir"
    fi

}

lnS() {
    local target=$1
    local link=$2

    if [[ -L $link ]]; then
        local existing
        existing=$(readlink "$link")
        if [[ $target == "$existing" ]]; then
            return 0
        fi
    fi

    assertSafePath "$link" || return 1

    if [[ -f $link ]] && [[ ! -L $link ]]; then
        rmFile "$link"
    fi

    set -- ln -vfs "$target" "$link"
    local parent=$(dirname "$link")

    mkdirP "$parent"

    if [[ ! -w $parent ]]; then
        askForSudo "$@" || return 1
        set -- sudo "$@"
    fi
    debugCmd "$@"
}

rmFile() {
    local file=${1:?filename required}

    assertSafePath "$file" || return 1

    if [[ ! -e $file ]]; then
        return 0
    fi

    set -- rm -v "$file"
    if [[ ! -w $file ]]; then
        log "warn" "$file is not writeable by your shell user. Using sudo to delete"
        askForSudo "$@" || return 1
        set -- sudo rm -v "$file"
    fi
    debugCmd "$@"
}

rmDir() {
    local dir=${1:?directory name required}

    assertSafePath "$dir" || return 1

    if [[ ! -e $dir ]]; then
        debug "rmDir: $dir does not exist"
        return 0
    fi

    if [[ ! -d $dir ]]; then
        log error "rmDir: $dir exists but is not a directory"
        return 1
    fi

    set -- rm -r "$dir"
    if [[ ! -w $dir ]]; then
        log "warn" "$dir is not writeable by your shell user. Using sudo to delete"
        askForSudo "$@" || return 1
        set -- sudo rm -r "$dir"
    fi
    debugCmd "$@"
}

quoteCmd() {
    local cmd=$(printf '%q ' "$@")
    printf "${cmd% }"
}

debugCmd() {
    local cmd=$(quoteCmd "$@")
    debug "Running:" "$cmd"

    local tmp=$(mktemp -d)

    local flags="$-"
    set +e
    "$@" > "$tmp/stdout" 2> "$tmp/stderr"

    local status=$?

    set "-$flags"

    if [[ $status -ne 0 ]]; then
        log error "Command returned non-zero: $status"
        log error "stdout:"
        log error - < "$tmp/stdout"
        log error "stderr:"
        log error - < "$tmp/stderr"
    else
        debug - < "$tmp/stdout"
        debug - < "$tmp/stderr"
    fi
    rm -rf "$tmp"

    return $status
}

checkDocker() {
    command -v docker &> /dev/null || {
        log "error" "No docker executable found. Is docker installed?"
        exit 1
    }

    docker info &> /dev/null || {
        log "error" "Running \`docker info\` failed. Is the docker daemon running?"
        exit 1
    }
}

dockerSocket() {
    local possible=(
        "$HOME"/Library/Containers/com.docker.docker/Data/docker.sock
        /var/lib/docker.sock
        /var/run/docker.sock
    )

    for sock in "${possible[@]}"; do
        if [[ -S $sock ]]; then
            printf "$sock"
            return
        fi
    done

    log "error" "No docker control socket found. Is docker installed and running?"
    exit 1
}

checkPaths() {
    local prefix=$(getInstallPrefix)
    local failed=0

    if [[ $DEV_MODE == 1 ]] && [[ ! -d $DEV_SOURCE ]]; then
        failed=1
        log "error" "NorthStack was started in DEV_MODE but the dev path ($DEV_SOURCE) does not exist."
    fi

    if [[ ! -d $prefix/northstack ]]; then
        failed=1
        log "error" "NorthStack assets ($prefix/northstack) are missing"
    fi

    if [[ $failed == 1 ]]; then
        exit 1
    fi
}

buildDockerImage() {
    local ctx=$1
    local tag=${2:-northstack}

    log info "building the northstack docker image"

    local failed=0

    show_spinner_cmd debugCmd docker \
        build \
        -t "$tag" \
        --label "com.northstack=1" \
        "$ctx"
}

installComposerDeps() {
    local ctx=$1

    debug "Installing dependencies in $ctx"

    set -- composer install --ignore-platform-reqs

    if iHave composer; then
        debug "Using natively-installed composer"
        set -- "$@" -d "$ctx"
    else
        debug "Composer not found--using docker to install"

        docker \
            volume \
            create \
            --label com.northstack=1 \
            ns-composer-cache

        set -- docker run \
            --rm \
            --user "$(id -u):$(id -g)" \
            --volume ns-composer-cache:/tmp \
            --volume "${ctx}:/app" \
            "$@"
    fi
    show_spinner_cmd debugCmd "$@"
    debug "Completed all of the composer install stuff. Onward!"
}

# Display text in a chosen color
# @param $color text color
# @param $message
# param bool $newline return at the end of the line(defaults to true)
#param $backgroundColor optional, defaults to transparent
# shellcheck disable=SC2034
colorText() {
    local color=$1
    local prefix=$'\e['
    local end=$'\e[0m'
    local background=${4:-""}

    local textPrefix="38;5;"

    local red="196"
    local orange="208"
    local yellow="226"
    local green="82"
    local cyan="45"
    local blue="27"
    local purple="129"
    local magenta="199"
    local black="0"
    local white="255"
    local grey="242"

    color=${!color:=}

    if [[ -z "$background" ]]; then
        color=${prefix}${textPrefix}${color}m
    else
        local bgPrefix="48;5;"
        background=${!background:=}
        color=${prefix}${textPrefix}${color}m${prefix}${bgPrefix}${background}m
    fi

    local defaultMSG=""
    local defaultNewLine=true

    local message=${2:-$defaultMSG}    # Defaults to default message.
    local newLine=${3:-$defaultNewLine}

    echo -en "${color}${message}${end}"
    if [ "$newLine" = true ]; then
        echo
    fi

    return
}

show_spinner_cmd() {
    if shellIsInteractive; then
        "$@" &
        show_spinner_pid
    else
        "$@"
    fi
}

show_spinner_pid() {
    local -r pid="$!"
    local -r delay='0.25'
    local spinstr='\|/-'
    if ! command -v tput > /dev/null || ! shellIsInteractive; then
        wait
        return
    fi
    local temp
    tput civis
    while ps a | awk '{print $1}' | grep -q "${pid}"; do
        temp="${spinstr#?}"
        printf "[%c]  " "${spinstr}"
        spinstr=${temp}${spinstr%"${temp}"}
        sleep "${delay}"
        printf "\b\b\b\b\b\b"
    done
    tput cnorm
    printf "    \b\b\b\b"
}
