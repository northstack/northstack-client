log() {
    declare -l level=$1

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
    local default=/usr/local
    local INSTALL_PATH=${INSTALL_PATH:-}

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

copyFiles() {
    local src=$1
    local dest=$2

    if [[ -d "$src" ]]; then
        rsyncDirs "$src" "$dest"
        return
    fi

    dest_dir=$(dirname "$dest")
    if [[ -w $dest_dir ]] && [[ -w $dest ]]; then
        log "info" "cp -av "$src" "$dest""
        cp -av "$src" "$dest" | debug -
    else
        log "warn" "$dest is not writeable by your shell user. Using sudo to copy"

        log "info" "sudo mkdir -pv "$dest_dir""
        sudo mkdir -pv "$dest_dir" | debug -

        log "info" "sudo cp -av "$src" "$dest""
        sudo cp -av "$src" "$dest" | debug -
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
        rsync="sudo ${rsync}"
    fi

    $rsync "$src" "$dest"
}

mkdirP() {
    local dir=$1
    local parent=$dir
    while [[ ! -d $parent ]]; do
        parent=$(dirname "$dir")
    done
    if [[ -w $parent ]]; then
        mkdir -pv "$dir"
    else
        log "warn" "$parent is not writeable by your shell user. Using sudo to mkdir"
        sudo mkdir -pv "$dir"
    fi

}

lnS() {
    local target=$1
    local link=$2
    ln -vfs "$target" "$link"
}

rmFile() {
    local f=$1

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
        /var/lib/docker.sock
        /var/run/docker.sock
        $HOME/Library/Containers/com.docker.docker/Data/docker.sock
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

    local group="$(stat "$sock" --printf='%G')"
    local gid="$(stat "$sock" --printf='%g')"

    debug "Detected docker group: $group, gid: $gid"

    log info "building the northstack docker image"

    local outfile=$(mktemp)
    local failed=0

    set +e
    docker build \
        --build-arg DOCKER_GID="$gid" \
        --build-arg DOCKER_GROUP="$group" \
        -t "$tag" \
        "$ctx" \
    &> "$outfile"

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

    log debug "Installing dependencies in $ctx"

    docker run --rm \
        --volume "${ctx}:/app" \
        composer install --ignore-platform-reqs \
        2>&1 \
    | debug -
}
