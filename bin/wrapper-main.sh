main() {
    local NS_PWD=${NS_PWD:-}
    local VOLUMESOCK=""

    if [[ -z $NS_PWD ]]; then
        debug "Using default workdir ($PWD); set \$NS_PWD to override"
        NS_PWD=$PWD
    fi

    checkDocker
    checkPaths

    local args=("$@")

    local NS_UID=$UID
    GID=$(id -g)

    if [[ $(uname) == Darwin ]]; then
        NS_UID=$((UID + 2000))
        GID=$((GID + 2000))
    fi

    socket=$(dockerSocket)

    prefix="$(getInstallPrefix)"
    ns_lib="${prefix}/lib/northstack"

    local DEBUG=${DEBUG:-0}

    set -- docker run \
        -ti \
        --rm \
        -e DEBUG="$DEBUG" \
        -e HOME="$HOME" \
        -e NS_PWD="$NS_PWD" \
        -e NORTHSTACK_UID=$NS_UID \
        -e NORTHSTACK_GID=$GID \
        --volume "$NS_PWD:$NS_PWD" \
        --volume "$HOME":"$HOME" $VOLUMESOCK \
        --volume "$socket":"$socket" \
        --init

    if [[ $DEV_MODE == 1 ]]; then
        debug "Running in DEV mode"
        set -- "$@" \
            --volume "$DEV_SOURCE":/app \
            -e NS_LIB="$DEV_SOURCE"
    else
        set -- "$@" -e NS_LIB="$ns_lib"
    fi

    if [[ $socket == "$HOME/Library/Containers/com.docker.docker/Data/docker.sock" ]]; then
        set -- "$@" --volume /var/run/docker.sock:/var/run/docker.sock
    fi

    set -- "$@" northstack "${args[@]}"
    debug "Running docker:" "$(quoteCmd "$@")"
    exec "$@"
}
