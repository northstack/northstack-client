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
    docker container ls -a --filter="$filter" --format='{{ .Names }}'
    prune container "$filter"

    docker volume ls --filter="$filter"
    prune volume "$filter"

    docker network ls --filter="$filter"
    prune network "$filter"

    echo "Removing $tmp"
    rm -rf "$tmp"
}

trap cleanup EXIT


checkContainer() {
    local id=$1
    local timeout=30

    echo -n "Checking on $id"

    local status=0
    local health=0
    until [[ $status == "running" ]] && [[ $health == "healthy" ]]; do
        echo -n .
        status=$(docker container inspect "$id" --format '{{ lower .State.Status }}')
        health=$(docker container inspect "$id" --format '{{ lower .State.Health.Status }}')
        timeout=$(( timeout - 1))
        if (( timeout < 1)); then
            echo
            echo "Reached timeout waiting for container to be up and healthy"
            echo "ID: $id, Status: $status, Health: $health"
            exit 1
        fi
        sleep 1
    done

    echo
    echo "container $id is up and ready to go"
}

checkServices() {
    local app=$1
    local filter="label=com.northstack.app.id=$app"
    docker container ls -a --filter="$filter" --format '{{ .Names }}' | while read c; do
        checkContainer "$c"
    done
}

rsync -a "$PWD/tests/testdata/" "$tmp"

ns="$BDIR/bin/northstack -vvv"

for app in $tmp/*; do
    cd "$app"

    appId=$(jq -r .prod < config/environment.json)
    echo "Testing $app $appId"

    $ns app:localdev:run build
    $ns app:localdev:run config > docker-compose.yml
    sed -e "s|/northstack/docker/|$BDIR/docker/|g" -i -- docker-compose.yml
    $ns app:localdev:start -d

    echo "Validating that services are up and running"
    checkServices "$appId"

    echo "Running stack-specific tests in: $app"

    ./run-stack-tests.sh
    docker-compose down -t 0
    $ns app:localdev:stop
    cd -
done
