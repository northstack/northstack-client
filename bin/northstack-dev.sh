#!/usr/bin/env bash

# Wrapper for running the northstack cli in docker
# Handles uid mapping, and given access to the docker sock so northstack can start local dev

log() {
    local level=${1,,}

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
    [[ $DEBUG == 1 ]] && log "debug" "$@"
}

log "Running DEV northstack source in SOURCE"

if [[ -z $NS_PWD ]]; then
    debug "Using default workdir ($PWD); set \$NS_PWD to override"
    NS_PWD=$PWD
fi

if [[ -S /var/lib/docker.sock ]]; then
    socket=/var/lib/docker.sock
elif [[ -S $HOME/Library/Containers/com.docker.docker/Data/docker.sock ]]; then
    # the control socket likes to hide here on OSX
    socket=$HOME/Library/Containers/com.docker.docker/Data/docker.sock
elif [[ -S /var/run/docker.sock ]]; then
    # the control socket likes to hide here on OSX
    socket=/var/run/docker.sock
else
    log "Error: no docker control socket found. Is docker installed and running?"
    exit 1
fi

GID=$(id -g)

docker run -ti --rm \
    -e DEBUG=$DEBUG \
    -e HOME=$HOME \
    -e NS_PWD="$NS_PWD" \
    --user=$UID:$GID \
    --userns=host \
    --volume "$NS_PWD:$NS_PWD" \
    --volume $HOME:$HOME \
    --volume /etc/passwd:/etc/passwd \
    --volume ${socket}:/var/lib/docker.sock \
    --volume SOURCE:/app \
    --init \
    northstack "$@"
