#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib.sh"

@test "userOwnership reports the current user and group to be passed to chown" {
    assert equal northstack:northstack "$(userOwnership)"
}
