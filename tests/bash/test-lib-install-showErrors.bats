#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib-install.sh"

@test "showErrors shows nothing if there are no errors" {
    run showErrors
    assert equal 0 "$status"
    assert stringContains "No errors detected" "$output"
}

@test "showErrors shows any recorded errors from setError" {
    setError foo "my foo error"
    setError bar "my bar error"
    run showErrors
    assert stringContains "my foo error" "$output"
    assert stringContains "my bar error" "$output"
}
