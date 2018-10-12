#!/usr/bin/env bash
set -e

CDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

source $CDIR/lib.sh

$CDIR/build.sh

install_path="$(getInstallPath)"
copyFile "$CDIR/northstack.sh" "${install_path}/northstack"

