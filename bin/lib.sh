log() {
    local level="$(echo "$1" | tr '[:upper:]' '[:lower:]')"

    case $level in
        info|debug|warn|error)
            shift;;
        *)
            level='info';;
    esac

    ts=$(date '+%Y-%m-%d %H:%M:%S')
    echo -e "[$ts] [$level] $@" > /dev/stderr
}

debug() {
    if [[ $DEBUG == 1 ]]; then
        log "debug" "$@"
    fi
}

getInstallPath() {
    local default=/usr/local/bin

    if [[ -z $INSTALL_PATH ]]; then
        log "Using default install path ($default)"
        log "You can change this behavior by setting the \$INSTALL_PATH environment variable"
        INSTALL_PATH=$default
    else
        log "Using install path: $INSTALL_PATH"
    fi
    printf ${INSTALL_PATH%*/}
}

copyFile() {
    local src=$1
    local dest=$2

    dest_dir=$(dirname "$dest")
    if [[ -w $dest_dir ]] && [[ -w $dest ]]; then
        cp -v "$src" "$dest"
    else
        log "warn" "$dest is not writeable by your shell user. Using sudo to copy"
        sudo cp -v "$src" "$dest"
    fi
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
