#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib.sh"

@test "getCwd gets the directory containing the file it was defined in" {
    assert equal "$BIN_DIR" "$(getCwd)"
}
