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
    assert equal "" "$INSTALL_ERRORS_LIBRARY_PATH"
    assert equal "" "$INSTALL_ERRORS_BINARY_PATH"
}

@test "checkPathPermissions warns if we can't write to the bin dir" {
    mkdir -p /tmp/prefix/path/here/bin
    sudo chown root:root /tmp/prefix/path/here/bin
    checkPathPermissions /tmp/prefix/path/here
    assert equal "" "$INSTALL_ERRORS_LIBRARY_PATH"
    assert not equal "" "$INSTALL_ERRORS_BINARY_PATH"
    run showErrors
    assert stringContains "/tmp/prefix/path/here/bin" "$output"
    assert stringContains "/tmp/prefix/path/here/bin/northstack" "$output"
    assert not stringContains "/tmp/prefix/path/here/northstack" "$output"
}

@test "checkPathPermissions warns if we can't write to the northstack binary" {
    mkdir -p /tmp/prefix/path/here/bin
    sudo touch /tmp/prefix/path/here/bin/northstack
    checkPathPermissions /tmp/prefix/path/here
    assert equal "" "$INSTALL_ERRORS_LIBRARY_PATH"
    assert not equal "" "$INSTALL_ERRORS_BINARY_PATH"
    run showErrors
    assert stringContains "/tmp/prefix/path/here/bin" "$output"
    assert not stringContains "/tmp/prefix/path/here/northstack" "$output"
}

@test "checkPathPermissions warns if we can't write to the northstack library dir" {
    mkdir -p /tmp/prefix/path/here/bin
    sudo mkdir -p /tmp/prefix/path/here/northstack
    checkPathPermissions /tmp/prefix/path/here
    assert not equal "" "$INSTALL_ERRORS_LIBRARY_PATH"
    assert equal "" "$INSTALL_ERRORS_BINARY_PATH"
    run showErrors
    assert not stringContains "/tmp/prefix/path/here/bin" "$output"
    assert stringContains "/tmp/prefix/path/here/northstack" "$output"
}

@test "checkPathPermissions warns if we can't write to anything" {
    sudo mkdir -p /tmp/prefix/path/here/{northstack,bin}
    checkPathPermissions /tmp/prefix/path/here
    assert not equal "" "$INSTALL_ERRORS_LIBRARY_PATH"
    assert not equal "" "$INSTALL_ERRORS_LIBRARY_PATH"
    run showErrors
    assert equal 1 $status
    assert stringContains "/tmp/prefix/path/here/bin" "$output"
    assert stringContains "/tmp/prefix/path/here/northstack" "$output"
}

@test "checkPathPermissions warns if we can't write to parent dirs" {
    sudo mkdir -p /tmp/prefix/path
    checkPathPermissions /tmp/prefix/path/here
    assert not equal "" "$INSTALL_ERRORS_LIBRARY_PATH"
    assert not equal "" "$INSTALL_ERRORS_LIBRARY_PATH"
    run showErrors
    assert equal 1 $status
    assert stringContains "/tmp/prefix/path/here/bin" "$output"
    assert stringContains "/tmp/prefix/path/here/northstack" "$output"
}
