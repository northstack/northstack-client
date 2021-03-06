#!/usr/bin/env bash
set -e

CDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
BDIR=$(dirname $CDIR)
cd $BDIR

export DEBUG=1
source "$BDIR/bin/lib.sh"

buildDockerImage .

group=$(id -gn)
gid=$(id -g)

docker build \
    -t northstack-test \
    -f Dockerfile-test \
    --build-arg NORTHSTACK_USER=ns \
    --build-arg NORTHSTACK_UID=$uid \
    --build-arg NORTHSTACK_GROUP=ns \
    --build-arg NORTHSTACK_GID=$gid \
    --build-arg DOCKER_GROUP=$docker_group \
    --build-arg DOCKER_GID=$docker_gid \
    --label "com.northstack=1" \
    .

NS_PWD="$BDIR"
mkdir -p "$BDIR/.tmp"
chmod 777 "$BDIR/.tmp"

docker run \
    -it \
    -e DEBUG=$DEBUG \
    -e HOME=$HOME \
    -e NS_PWD="$NS_PWD" \
    -e NS_LIB="$BDIR" \
    -e PAUSE=$PAUSE \
    -e NORTHSTACK_USER=ns \
    -e NORTHSTACK_UID=$uid \
    -e NORTHSTACK_GROUP=ns \
    -e NORTHSTACK_GID=$gid \
    -e DOCKER_GROUP=$docker_group \
    -e DOCKER_GID=$docker_gid \
    -e RUNNING_IN_DOCKER=1 \
    -e TEST_LOCALDEV=${TEST_LOCALDEV:-0} \
    --label "com.northstack=1" \
    --volume /var/run/docker.sock:/var/run/docker.sock \
    --volume "$NS_PWD:$NS_PWD" \
    --volume "$BDIR/.tmp:/app/.tmp" \
    --workdir "/app" \
    --init \
    northstack-test \
    /app/tests/run-all.sh
