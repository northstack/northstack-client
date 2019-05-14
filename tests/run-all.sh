#!/usr/bin/env bash
set -e

CDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
BDIR=$(dirname $CDIR)
cd $BDIR

echo "--- :php: :clippy: PHPStan"
./vendor/bin/phpstan analyze --level 0 src

echo "--- :php: :parrot: Running PHPUnit"
./vendor/bin/phpunit tests

if [[ ${TEST_LOCALDEV:-0} == 1 ]]; then
    ./tests/test-localdev.sh
fi
