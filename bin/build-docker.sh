#!/usr/bin/env bash
set -e

CDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
BASE="$( dirname "$CDIR")"

source "$CDIR/lib.sh"

log info "building the northstack docker image"

cd "$BASE"
docker build -t northstack . | debug -

log info "northstack image built successfully"
