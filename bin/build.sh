#!/usr/bin/env sh
set -e

CDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
BASE="$( dirname "$CDIR")"

cd $BASE
docker build -t northstack .
