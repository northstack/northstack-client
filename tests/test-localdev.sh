#!/usr/bin/env bash
set -eu

CDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
BDIR=$(dirname $CDIR)
cd $BDIR

. ./bin/lib.sh


mkdir -p $PWD/.tmp
tmp=$(mktemp --directory --tmpdir="$PWD/.tmp")

cleanup() { echo "Removing $tmp"; rm -rf "$tmp"; }
trap cleanup EXIT

rsync -a "$PWD/tests/testdata/" "$tmp"

ns="$BDIR/bin/northstack"

for app in $tmp/*; do
    echo "Testing $app"
    cd "$app"
    $ns app:localdev:run config > docker-compose.yml
    $ns app:localdev:start -d
    ./run-stack-tests.sh
    $ns app:localdev:stop
    cd -
done
