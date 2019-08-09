#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib-install.sh"

@test "checksumFile runs md5sum" {
    checksumFile "$(mktemp)"
}
