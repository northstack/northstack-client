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
            shift;;
        debug)
            level_color=$blue
            shift;;
        warn)
            level_color=$magenta
            shift;;
        error)
            level_color=$red
            shift;;
        *)
            level_color=$green
            level='info';;
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
            done;;
        *)
            printf "$template" "$@" > /dev/stderr;;
    esac

}

getCwd() {
    echo "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
}

debug() {
    local DEBUG=${DEBUG:-0}
    if [[ $DEBUG == 1 ]]; then
        log "debug" "$@"
    fi
}

setInstallPrefix() {
    local default=$HOME/.local
    ${INSTALL_PATH:=}

    if [[ -z $INSTALL_PATH ]]; then
        log "Using default install prefix ($default)"
        log "You can change this behavior by setting the \$INSTALL_PATH environment variable"
        INSTALL_PATH=$default
    else
        log "Using install path: $INSTALL_PATH"
    fi
    printf ${INSTALL_PATH%*/}
}

getInstallPrefix() {
    local binDir="$(getCwd)"
    printf $(dirname "$binDir")
}

askForSudo() {
    local cmd=$@

    [[ ${NORTHSTACK_ALLOW_SUDO:-0} == 1 ]] && return 0
    echo "Asking permission to run the following command with sudo:"
    echo "$cmd"
    echo "You can disable this check by setting NORTHSTACK_ALLOW_SUDO=1"

    read -p "Enter y/n : " answer

    [[ $answer == y ]] && return 0

    return 1
}

copyFiles() {
    local src=$1
    local dest=$2

    if [[ -d "$src" ]]; then
        if [[ ! -d "$dest" ]]
        then
            mkdir -p "$dest"
        fi

        debugCmd cp -Rf "$src/*" "$dest/"
        # rsyncDirs "$src" "$dest"
        return
    fi

    dest_dir=$(dirname "$dest")
    echo "Dir: $dest_dir"
    echo "file: $dest"
    whoami
    id -u
    id -g
    if [[ -w $dest_dir ]] && [[ -w $dest ]]; then
        debugCmd cp -av "$src" "$dest"
    else
        log "warn" "$dest is not writeable by your shell user. Using sudo to copy"
        askForSudo mkdir -pv "$dest_dir" \; cp -av "$src" "$dest"
        debugCmd sudo mkdir -pv "$dest_dir"
        debugCmd sudo cp -av "$src" "$dest"
    fi
}

rsyncDirs() {
    local src=$1
    local dest=$2

    src=${src%/}; dest=${dest%/}
    src=${src}/; dest=${dest}/

    debug "Rsync from $src -> $dest"

    if [[ ! -d $dest ]]; then
        mkdirP "$dest"
    fi

    local rsync="rsync -HavzuP --exclude=.git --exclude=.tmp"

    if [[ ! -w $dest ]]; then
        log "warn" "$dest is not writeable by your shell user; using sudo to copy files"
        askForSudo $rsync "$src" "$dest"
        rsync="sudo ${rsync}"
    fi

    debugCmd $rsync "$src" "$dest"
}

mkdirP() {
    local dir=$1
    local parent=$dir
    while [[ ! -d $parent ]]; do
        debug "Parent dir not found, making dir: ${parent}"
        parent=$(dirname "$parent")
    done
    if [[ -w $parent ]]; then
        debugCmd mkdir -pv "$dir"
    else
        log "warn" "$parent is not writeable by your shell user. Using sudo to create $dir (164)"
        askForSudo mkdir -pv "$dir"
        debugCmd sudo mkdir -pv "$dir"
    fi

}

lnS() {
    local target=$1
    local link=$2

    if [[ -f $link ]] && [[ ! -h $link ]]; then
        rmFile "$link"
    fi

    local ln="ln -vfs"
    local parent=$(dirname "$link")

    if [[ ! -d $parent ]]; then
        mkdirP "$parent"
    fi

    if [[ ! -w $parent ]]; then
        askForSudo "$ln" "$target" "$link" && ln="sudo $ln"
    fi
    debugCmd "$ln" "$target" "$link"
}

rmFile() {
    local file=$1

    local rm="rm -v "

    [[ -w $file ]] || {
        log "warn" "$file is not writeable by your shell user. Using sudo to delete"
        askForSudo $rm "$file"
        rm="sudo ${rm}"
    }
    debugCmd $rm "$file"
}

debugCmd() {
    local cmd="$@"

    log info "Running: $cmd"

    local tmp=$(mktemp -d)

    set +e
    $cmd > "$tmp/stdout" 2> "$tmp/stderr"

    local status=$?

    set -e

    if [[ $status -ne 0 ]]; then
        log error "Command returned non-zero: $status"
        log error "stdout:"
        cat "$tmp/stdout" | log error -
        log error "stderr:"
        cat "$tmp/stderr" | log error -
    else
        debug - < "$tmp/stdout"
        debug - < "$tmp/stderr"
    fi
    rm -rf "$tmp"

    return $status
}

checkDocker() {
    which docker &>/dev/null || {
        log "error" "No docker executable found. Is docker installed?"
        exit 1
    }

    docker info &>/dev/null || {
        log "error" "Running \`docker info\` failed. Is the docker daemon running?"
        exit 1
    }
}

dockerSocket() {
    local possible=(
        $HOME/Library/Containers/com.docker.docker/Data/docker.sock
        /var/lib/docker.sock
        /var/run/docker.sock
    )

    for sock in ${possible[@]}; do
        if [[ -S $sock ]]; then
            printf "$sock"
            return
        fi
    done

    log "error" "No docker control socket found. Is docker installed and running?"
    exit 1
}

getGid() {
    id -g
}

checkPaths() {
    local prefix=$(getInstallPrefix)
    local failed=0

    if [[ $DEV_MODE == 1 ]] && [[ ! -d $DEV_SOURCE ]]; then
        failed=1
        log "error" "NorthStack was started in DEV_MODE but the dev path ($DEV_SOURCE) does not exist."
    fi

    if [[ ! -d $prefix/lib/northstack ]]; then
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

    local sock="$(dockerSocket)"

    log info "building the northstack docker image"

    local outfile=$(mktemp)
    local failed=0

    set +e
    docker build \
        -t "$tag" \
        --label "com.northstack=1" \
        "$ctx" \
    &> "$outfile" & show_spinner_pid

    if [[ $? -ne 0 ]]; then
        log "error" "image build failed:"
        log "error" - < $outfile
        failed=1
    else
        log info "northstack image built successfully"
        debug - < $outfile
    fi
    set -e
    rm "$outfile"

    if [[ $failed == 1 ]]; then
        exit 1
    fi
}

installComposerDeps() {
    local ctx=$1

    debug "Installing dependencies in $ctx"

    debugCmd docker run --rm \
        --volume "${ctx}:/app" \
        composer install --ignore-platform-reqs

    debug "Completed all of the composer install stuff. Onward!"
}

# Display text in a chosen color
# @param $color text color
# @param $message
# param bool $newline return at the end of the line(defaults to true)
#param $backgroundColor optional, defaults to transparent
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

    eval color=\$$color # ʕノ•ᴥ•ʔノ ︵ ┻━┻ variable variables

    if [ -z "$background" ] # if no bg color is defined
    then
        color=${prefix}${textPrefix}${color}m
    else
        local bgPrefix="48;5;"
        eval background=\$$background # ʕノ•ᴥ•ʔノ ︵ ┻━┻ use the same colors but for text bg
        color=${prefix}${textPrefix}${color}m${prefix}${bgPrefix}${background}m
    fi

    local defaultMSG="";
    local defaultNewLine=true;

    local message=${2:-$defaultMSG};   # Defaults to default message.
    local newLine=${3:-$defaultNewLine};

    echo -en "${color}${message}${end}";
    if [ "$newLine" = true ] ; then
        echo;
    fi

    return;
}

show_spinner_cmd()
{
  local cmd="$@"
  $cmd &
  show_spinner_pid
}

show_spinner_pid()
{
  local -r pid="$!"
  local -r delay='0.5'
  local spinstr='\|/-'
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
