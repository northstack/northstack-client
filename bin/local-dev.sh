#!/usr/bin/env bash
set -e

CDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
BASE="$( dirname "$CDIR")"

source "$CDIR"/lib.sh

checkDocker

socket=$(dockerSocket)
GID=$(id -g)

docker run \

