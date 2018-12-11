#!/usr/bin/env bash
set -e

CDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
BDIR=$(dirname $CDIR)
cd $BDIR

export DEBUG=1
source "$BDIR/bin/lib.sh"
buildDockerImage .
docker build \
    -t northstack-test \
    -f Dockerfile-test \
    --build-arg NORTHSTACK_USER=$USER \
    --build-arg NORTHSTACK_UID=$UID \
    --build-arg NORTHSTACK_GROUP=$(id -gn) \
    --build-arg NORTHSTACK_GID=$(id -g) \
    --build-arg DOCKER_GROUP=$(stat /var/run/docker.sock --printf='%G') \
    .

NS_PWD="$BDIR"
mkdir -p "$BDIR/.tmp"

docker run \
    -it \
    -e DEBUG=$DEBUG \
    -e HOME=$HOME \
    -e NS_PWD="$NS_PWD" \
    -e NS_LIB="$BDIR" \
    -e PAUSE=$PAUSE \
    --network host \
    --volume /var/run/docker.sock:/var/run/docker.sock \
    --volume "$NS_PWD:$NS_PWD" \
    --volume "$BDIR/.tmp:/app/.tmp" \
    --workdir "/app" \
    --user=$UID:$(id -g) \
    --userns=host \
    --init \
    northstack-test \
    /app/tests/run-all.sh
