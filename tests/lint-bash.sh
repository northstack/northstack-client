#!/bin/bash

set -eu

CDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
BASE="$( dirname "$CDIR")"

"$CDIR"/build-bats.sh

BIN_DIR="$BASE/bin"
REPO=northstack/bats

failed=0
echo "+++ Linting bash files"
for f in "$BIN_DIR"/*.sh; do
    if [[ -L $f ]]; then
        continue
    fi
    f=$(basename "$f")
    echo "--- Linting: $f"
    docker run \
        --rm \
        --init \
        --volume "${BASE}:${BASE}:ro" \
        --workdir "$BIN_DIR" \
        -e BIN_DIR="$BIN_DIR" \
        --label com.northstack=1 \
        --entrypoint /bin/shellcheck \
        "$REPO:latest" \
        "$f" \
    || {
        echo "^^^ +++"
        echo Lint errors for $f
        failed=1
    }
done

exit $failed
