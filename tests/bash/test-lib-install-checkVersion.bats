#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib-install.sh"

@test "checkVersion records an error for missing binaries" {
    run checkVersion nope
    assert equal 1 "$status"
}
