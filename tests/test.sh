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
    .

NS_PWD="$BDIR"
mkdir -p "$BDIR/.tmp"

docker run \
    --rm \
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
    --entrypoint /app/tests/run-all.sh \
    --user=$UID:$(id -g) \
    --volume "/etc/passwd:/etc/passwd:ro" \
    --volume "/etc/group:/etc/group:ro" \
    --group-add="$(stat /var/run/docker.sock --printf='%G')" \
    --init \
    northstack-test

