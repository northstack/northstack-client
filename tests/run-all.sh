#!/usr/bin/env bash
set -e

CDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
BDIR=$(dirname $CDIR)
cd $BDIR

./vendor/bin/phpstan analyze --level 0 src

if [[ ${TEST_LOCALDEV:-0} == 1 ]]; then
    ./tests/test-localdev.sh
fi
