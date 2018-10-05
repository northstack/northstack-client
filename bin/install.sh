#!/usr/bin/env bash
set -e

CDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

$CDIR/build.sh

sudo cp $CDIR/northstack.sh /usr/local/bin/northstack

