#!/usr/bin/env bash
set -e

CDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
BASE="$( dirname "$CDIR")"

source $CDIR/lib.sh

$CDIR/build.sh

install_path="$(getInstallPath)"
copyFile "$CDIR/northstack-dev.sh" "${install_path}/northstack"

SOURCE=$(echo "$BASE" | sed -e 's/[\/&]/\\&/g')
sed -i -e "s/SOURCE/${SOURCE}/g" "${install_path}/northstack"
