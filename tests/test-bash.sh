#!/bin/bash

set -eu

CDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
BASE="$( dirname "$CDIR")"

BIN_DIR="$BASE/bin"

stamp=$(stat -c %Y "$CDIR"/docker/Dockerfile)
bats_version=$(awk -F= '/^ARG BATS_VERSION/ {print $2}' "$CDIR"/docker/Dockerfile)

repo=northstack/bats
current=${repo}:${bats_version}

check=$(docker image ls "$current" --filter label=com.northstack.image.stamp="$stamp" -q)
if [[ -z $check ]]; then
    echo "--- Building northstack/bats docker image"
    docker build \
        --file "$CDIR/docker/Dockerfile" \
        --tag "$current" \
        --tag "${repo}:latest" \
        --label com.northstack=1 \
        --label com.northstack.image.stamp="$stamp" \
        "$CDIR/docker"
fi

echo "+++ Running bash tests"
if [[ $# -eq 0 ]]; then
    set -- bash
fi

docker run \
    --rm \
    --init \
    --volume "${BASE}:${BASE}" \
    --workdir "$BASE/tests" \
    -e BIN_DIR="$BIN_DIR" \
    -e DEBUG="${DEBUG:-}" \
    --label com.northstack=1 \
    "$repo":latest \
    "$@"


echo "+++ Linting bash files"
files=()
for f in "$BIN_DIR"/*.sh; do
    if [[ -f $f ]]; then
        f=$(basename "$f")
        files+=("$f")
    fi
done

docker run \
    --rm \
    --init \
    --volume "${BASE}:${BASE}:ro" \
    --workdir "$BIN_DIR" \
    -e BIN_DIR="$BIN_DIR" \
    --label com.northstack=1 \
    --entrypoint /bin/shellcheck \
    "$repo":latest \
    ${files[*]}
