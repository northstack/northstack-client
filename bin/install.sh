#!/usr/bin/env bash
set -eu

CDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
BASE="$( dirname "$CDIR")"

. "$CDIR"/lib.sh
. "$CDIR"/lib-install.sh

install
exit 0

checkDocker
buildDockerImage "$BASE"

wrapperFile=$(mktemp)

cleanup() {
    rm "$wrapperFile"
}

trap cleanup EXIT

isDev=0

if [[ ${0##*/} == "install-dev.sh" ]]; then
    log "info" "Installing in DEV mode"
    isDev=1

    log "info" "Installing dependencies with composer"
    installComposerDeps "$BASE"
fi

install_path="$(setInstallPrefix)"

"$CDIR"/build-wrapper.sh "$wrapperFile" "$BASE" "$isDev"


copyFiles "$wrapperFile" "${install_path}/bin/northstack"
copyFiles "${BASE}/docker" "${install_path}/lib/northstack/docker"
