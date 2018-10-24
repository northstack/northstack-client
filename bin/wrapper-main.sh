main() {
    if [[ -z $NS_PWD ]]; then
        debug "Using default workdir ($PWD); set \$NS_PWD to override"
        NS_PWD=$PWD
    fi

    checkDocker

    socket=$(dockerSocket)

    GID=$(getGid)

    DEV_ARGS=""
    if [[ $DEV_MODE == 1 ]]; then
        DEV_ARGS="--volume $DEV_SOURCE:/app "
    fi

    docker run -ti --rm \
        -e DEBUG=$DEBUG \
        -e HOME=$HOME \
        -e NS_PWD="$NS_PWD" \
        --user=$UID:$GID \
        --userns=host \
        --volume "$NS_PWD:$NS_PWD" \
        --volume $HOME:$HOME \
        --volume /etc/passwd:/etc/passwd \
        --volume "$socket":/var/lib/docker.sock \
        --init \
        $DEV_ARGS \
        northstack "$@"
}
