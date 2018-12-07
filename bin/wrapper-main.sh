main() {
    local NS_PWD=${NS_PWD:-}

    if [[ -z $NS_PWD ]]; then
        debug "Using default workdir ($PWD); set \$NS_PWD to override"
        NS_PWD=$PWD
    fi

    checkDocker
    checkPaths

    socket=$(dockerSocket)
    docker_group=$(stat "$socket" --printf='%G')

    GID=$(getGid)

    prefix="$(getInstallPrefix)"
    ns_lib="${prefix}/lib/northstack"

    local DEBUG=${DEBUG:-0}

    if [[ $DEV_MODE == 1 ]]; then

        debug "Running in DEV mode"
        ns_lib=$DEV_SOURCE

        docker run -ti --rm \
            -e DEBUG=$DEBUG \
            -e HOME=$HOME \
            -e NS_PWD="$NS_PWD" \
            -e NS_LIB="$ns_lib" \
            --user=$UID:$GID \
            --userns=host \
            --group-add="$docker_group" \
            --volume "$NS_PWD:$NS_PWD" \
            --volume $HOME:$HOME \
            --volume "$socket":/var/run/docker.sock \
            --volume "$DEV_SOURCE":/app \
            --volume "/etc/passwd:/etc/passwd:ro" \
            --volume "/etc/group:/etc/group:ro" \
            --init \
            northstack "$@"
    else
        docker run -ti --rm \
            -e DEBUG=$DEBUG \
            -e HOME=$HOME \
            -e NS_PWD="$NS_PWD" \
            -e NS_LIB="$ns_lib" \
            --user=$UID:$GID \
            --userns=host \
            --group-add="$docker_group" \
            --volume "$NS_PWD:$NS_PWD" \
            --volume $HOME:$HOME \
            --volume "$socket":/var/run/docker.sock \
            --volume "/etc/passwd:/etc/passwd:ro" \
            --volume "/etc/group:/etc/group:ro" \
            --init \
            northstack "$@"
    fi

}
