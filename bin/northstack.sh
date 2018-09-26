#!/usr/bin/env sh

# Wrapper for running the northstack cli in docker
# Handles uid mapping, and given access to the docker sock so northstack can start local dev

docker run -ti --rm \
    --user=$UID --userns=host \
    --volume `pwd`:/current \
    --volume $HOME:$HOME \
    --volume /etc/passwd:/etc/passwd \
    --volume /var/lib/docker.sock:/var/lib/docker.sock \
    --init \
    northstack "$@"
