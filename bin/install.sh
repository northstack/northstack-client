#!/usr/bin/env bash
set -e

CDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
BASE="$( dirname "$CDIR")"

source "$CDIR"/lib.sh

checkDocker

"$CDIR"/build-docker.sh

wrapperFile=$(mktemp)

cleanup() {
    rm "$wrapperFile"
}

trap cleanup EXIT

if [[ ${0##*/} == "install-dev.sh" ]]; then
    log "info" "Installing in DEV mode"
    isDev=1
fi

install_path="$(setInstallPrefix)"

"$CDIR"/build-wrapper.sh "$wrapperFile" "$BASE" "$isDev"


copyFiles "$wrapperFile" "${install_path}/bin/northstack"
copyFiles "${BASE}/localdev-docker" "${install_path}/lib/northstack/localdev-docker"
