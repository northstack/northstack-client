#!/bin/bash

set -eu

CDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
BASE="$( dirname "$CDIR")"

"$CDIR"/build-bats.sh

BIN_DIR="$BASE/bin"
REPO=northstack/bats

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
    "$REPO:latest" \
    "$@"
