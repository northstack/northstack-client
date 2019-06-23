#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib-install.sh"

@test "complain exits nonzero and prints an error message" {
    run complain
    assert stringContains "Could not verify the minimum installation" "$output"
    assert equal 1 $status
}

@test "complain prints any errors recorded during installation" {
    setError foo "my foo error"
    run complain
    assert equal 1 $status
    assert stringContains "my foo error" "$output"
}
