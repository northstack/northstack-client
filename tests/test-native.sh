#!/usr/bin/env bash
set -e

CDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
BDIR=$(dirname $CDIR)
cd $BDIR

. ./bin/lib.sh

installComposerDeps "$PWD"

./tests/run-all.sh
