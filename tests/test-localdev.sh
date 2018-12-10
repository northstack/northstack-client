#!/usr/bin/env bash
set -eu

CDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
BDIR=$(dirname $CDIR)
cd $BDIR

. ./bin/lib.sh


mkdir -p $PWD/.tmp
tmp=$(mktemp --directory --tmpdir="$PWD/.tmp")

prune() {
    local resource=$1
    local filter=$2

    docker "$resource" prune --force --filter="$filter"
}

cleanup() {
    echo "Cleaning up docker resources"
    local filter="label=com.northstack.localdev=1"

    for c in $(docker container ls -a --filter="$filter" --format='{{ .Names }}'); do
        echo "Stopping container: $c"
        docker container stop -t 0 "$c"
    done
    docker container ls -a --filter="$filter"
    prune container "$filter"

    docker volume ls --filter="$filter"
    prune volume "$filter"

    docker network ls --filter="$filter"
    prune network "$filter"

    echo "Removing $tmp"
    rm -rf "$tmp"
}

trap cleanup EXIT

rsync -a "$PWD/tests/testdata/" "$tmp"

ns="$BDIR/bin/northstack"

for app in $tmp/*; do
    echo "Testing $app"
    cd "$app"
    $ns app:localdev:run config > docker-compose.yml
    $ns app:localdev:start -d
    ./run-stack-tests.sh
    $ns app:localdev:stop
    cd -
done
