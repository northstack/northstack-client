#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib-install.sh"

@test "setUserOptions defaults to \$HOME/northstack/docker" {
    export HOME=$(mktemp -d "$BATS_TMPDIR/home.XXXXXX")
    run setUserOptions
    assert stringContains "Using the default appDir" "$output"
}
