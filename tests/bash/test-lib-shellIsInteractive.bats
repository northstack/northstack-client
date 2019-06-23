#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib.sh"

setup() {
    sudo apk add expect
}

@test "shellIsInteractive checks if a shell is interactive" {
    ! shellIsInteractive

    # haven't found a reliable way to fake an interactive shell :(
}
