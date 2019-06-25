#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib-install.sh"

setup() {
    mkdir -p /tmp/prefix
}

teardown() {
    sudo rm -rv /tmp/prefix
}

@test "checkPathPermissions is chill when nothing needs changing" {
    mkdir -p /tmp/prefix/path/here
    checkPathPermissions /tmp/prefix/path/here
}

@test "checkPathPermissions warns if we can't write to the things we need to" {
    mkdir -p /tmp/prefix/path/here/bin
    sudo chown root:root /tmp/prefix/path/here/bin
    run checkPathPermissions /tmp/prefix/path/here
    assert equal 1 $status
    assert stringContains "/tmp/prefix/path/here/bin" "$output"
    assert stringContains "/tmp/prefix/path/here/bin/northstack" "$output"
    assert not stringContains "/tmp/prefix/path/here/northstack" "$output"

    sudo chown root:root /tmp/prefix/path/here
    run checkPathPermissions /tmp/prefix/path/here
    assert equal 1 $status
    assert stringContains "/tmp/prefix/path/here/bin" "$output"
    assert stringContains "/tmp/prefix/path/here/northstack" "$output"
}
