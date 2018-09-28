#!/usr/bin/env sh
set -e

CDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
BASE="$( dirname "$CDIR")"

$CDIR/build.sh

cp $CDIR/northstack-dev.sh /usr/local/bin/northstack
SOURCE=$(echo "$BASE" | sed -e 's/[\/&]/\\&/g')
sed -i -e "s/SOURCE/${SOURCE}/g" /usr/local/bin/northstack

