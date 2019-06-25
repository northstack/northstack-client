#!/bin/bash

set -eu

CDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

stamp=$(stat -c %Y "$CDIR"/docker/Dockerfile)
bats_version=$(awk -F= '/^ARG BATS_VERSION/ {print $2}' "$CDIR"/docker/Dockerfile)

repo=northstack/bats
current=${repo}:${bats_version}

check=$(docker image ls \
        --quiet \
        --filter label=com.northstack.image.stamp="$stamp" \
        "$current")

if [[ -n $check ]]; then
    exit 0
fi

echo "--- Building northstack/bats docker image"

docker build \
    --file "$CDIR/docker/Dockerfile" \
    --tag "$current" \
    --tag "${repo}:latest" \
    --label com.northstack=1 \
    --label com.northstack.image.stamp="$stamp" \
    "$CDIR/docker" \
|| {
    echo "^^^ +++"
    exit 1
}
