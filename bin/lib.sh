#!/bin/bash

log() {
    ts=$(date '+%Y-%m-%d %H:%M:%S')
    echo -e "$ts\t$@" > /dev/stderr
}

getInstallPath() {
    local default=/usr/local/bin

    if [[ -z $INSTALL_PATH ]]; then
        log "Using default install path ($default)"
        log "You can change this behavior by setting the \$INSTALL_PATH environment variable"
        INSTALL_PATH=$default
    else
        log "Using install path: $INSTALL_PATH"
    fi
    printf ${INSTALL_PATH%*/}
}

copyFile() {
    local src=$1
    local dest=$2

    dest_dir=$(dirname "$dest")
    if [[ -w $dest_dir ]]; then
        cp -v "$src" "$dest"
    else
        log "Warning: $dest is not writeable by your shell user. Using sudo to copy"
        sudo cp -v "$src" "$dest"
    fi
}
