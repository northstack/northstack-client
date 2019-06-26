#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib.sh"

@test "shellIsInteractive checks if a shell is interactive" {
    skip "haven't found a reliable way to fake an interactive shell :("
    ! shellIsInteractive
}
