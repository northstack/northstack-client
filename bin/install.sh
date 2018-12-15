#!/usr/bin/env bash
set -eu

CDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
BASE="$( dirname "$CDIR")"

. "$CDIR"/lib.sh
. "$CDIR"/lib-install.sh


if [[ ${0##*/} == "install-dev.sh" ]]; then
    isDev=1
else
    isDev=0
fi

install "$BASE" "$isDev"
