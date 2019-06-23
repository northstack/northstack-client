#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib.sh"

@test "quoteCmd escapes things" {
    run quoteCmd echo "hi there! &\nhow're ya?"
    assert equal 0 $status
    assert equal "echo hi\ there\!\ \&\\nhow're\ ya?" "$output"
}
