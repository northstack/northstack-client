#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib.sh"

@test "setInstallPath defaults to ~/.local" {
    export INSTALL_PATH
    run setInstallPath
    assert equal 0 $status
    assert stringContains "Using default install prefix" "$output"
    assert stringContains "$HOME/.local" "$output"
}

@test "setInstallPath can be overridden by setting INSTALL_PATH" {
    export INSTALL_PATH=/opt
    run setInstallPath
    assert equal 0 $status
    assert not stringContains "Using default install prefix" "$output"
    assert stringContains "/opt" "$output"
}
