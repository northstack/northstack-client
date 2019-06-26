#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib-install.sh"

setup() {
    TMP=$BATS_TMPDIR/prefix
    mkdir -p "$TMP"
    export INSTALL_PREFIX=$TMP
}

teardown() {
   _sudo rm -rv "${TMP:-wut}"
}

@test "checkPathPermissions is chill when nothing needs changing" {
    mkdir -p "$TMP"/path/here
    checkPathPermissions "$TMP"/path/here
    assert equal "" "$INSTALL_ERRORS_LIBRARY_PATH"
    assert equal "" "$INSTALL_ERRORS_BINARY_PATH"
}

@test "checkPathPermissions warns if we can't write to the bin dir" {
    mkdir -p "$TMP"/path/here/bin
    _sudo chown root "$TMP"/path/here/bin
    export INSTALL_PREFIX=$TMP/path/here
    checkPathPermissions
    assert equal "" "$INSTALL_ERRORS_LIBRARY_PATH"
    assert not equal "" "$INSTALL_ERRORS_BINARY_PATH"
    run showErrors
    assert stringContains "$TMP/path/here/bin" "$output"
    assert stringContains "$TMP/path/here/bin/northstack" "$output"
    assert not stringContains "$TMP/path/here/northstack" "$output"
}

@test "checkPathPermissions warns if we can't write to the northstack binary" {
    mkdir -p "$TMP"/path/here/bin
    _sudo touch "$TMP"/path/here/bin/northstack
    export INSTALL_PREFIX="$TMP"/path/here
    checkPathPermissions "$TMP"/path/here
    assert equal "" "$INSTALL_ERRORS_LIBRARY_PATH"
    assert not equal "" "$INSTALL_ERRORS_BINARY_PATH"
    run showErrors
    assert stringContains "$TMP/path/here/bin" "$output"
    assert not stringContains "$TMP/path/here/northstack" "$output"
}

@test "checkPathPermissions warns if we can't write to the northstack library dir" {
    mkdir -p "$TMP"/path/here/bin
    _sudo mkdir -p "$TMP"/path/here/northstack
    export INSTALL_PREFIX="$TMP"/path/here
    checkPathPermissions
    assert not equal "" "$INSTALL_ERRORS_LIBRARY_PATH"
    assert equal "" "$INSTALL_ERRORS_BINARY_PATH"
    run showErrors
    assert not stringContains "$TMP/path/here/bin" "$output"
    assert stringContains "$TMP/path/here/northstack" "$output"
}

@test "checkPathPermissions warns if we can't write to anything" {
    _sudo mkdir -p "$TMP"/path/here/{northstack,bin}
    export INSTALL_PREFIX="$TMP"/path/here
    checkPathPermissions
    assert not equal "" "$INSTALL_ERRORS_LIBRARY_PATH"
    run showErrors
    assert equal 1 $status
    assert stringContains "$TMP/path/here/bin" "$output"
    assert stringContains "$TMP/path/here/northstack" "$output"
}

@test "checkPathPermissions warns if we can't write to parent dirs" {
    _sudo mkdir -p "$TMP"/path
    export INSTALL_PREFIX="$TMP"/path/here
    checkPathPermissions
    assert not equal "" "$INSTALL_ERRORS_LIBRARY_PATH"
    run showErrors
    assert equal 1 $status
    assert stringContains "$TMP/path/here/bin" "$output"
    assert stringContains "$TMP/path/here/northstack" "$output"
}

@test "checkPathPermissions warns if we can't write to the app dir" {
    _sudo mkdir -p "$TMP"/path/apps
    export INSTALL_PREFIX="$TMP"/path/here
    export INSTALL_APPDIR="$TMP"/path/apps
    checkPathPermissions
    assert not equal "" "$INSTALL_ERRORS_LIBRARY_PATH"
    assert not equal "" "$INSTALL_ERRORS_APPDIR_PATH"
    run showErrors
    assert equal 1 $status
    assert stringContains "$TMP/path/apps" "$output"
    assert stringContains "appdir" "$output"
}
