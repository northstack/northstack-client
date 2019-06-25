#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib.sh"

@test "setInstallPrefix defaults to ~/.local" {
    export INSTALL_PATH
    run setInstallPrefix
    assert equal 0 $status
    assert stringContains "Using default install prefix" "$output"
    assert stringContains "$HOME/.local" "$output"
}

@test "setInstallPrefix can be overridden by setting INSTALL_PATH" {
    export INSTALL_PATH=/opt
    run setInstallPrefix
    assert equal 0 $status
    assert not stringContains "Using default install prefix" "$output"
    assert stringContains "/opt" "$output"
}
