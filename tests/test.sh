#!/usr/bin/env bash
set -e

CDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
BDIR=$(dirname $CDIR)
cd $BDIR

docker build -t northstack-test .

docker run --rm --entrypoint /app/tests/run-all.sh northstack-test
