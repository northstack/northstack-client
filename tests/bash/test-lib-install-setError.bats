#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib-install.sh"

@test "setError sets an error message in a namespace" {
    assert equal "" "${INSTALL_ERRORS_FOO:-}"
    setError foo "my error"
    assert equal "my error" "$INSTALL_ERRORS_FOO"
}

@test "setError appends errors to existing namespaces" {
    assert equal "" "${INSTALL_ERRORS_FOO:-}"
    setError foo "my error"
    setError foo "my second error"
    assert stringContains "my error" "$INSTALL_ERRORS_FOO"
    assert stringContains "my second error" "$INSTALL_ERRORS_FOO"
}

@test "setError works inside a function" {
    assert equal "" "${INSTALL_ERRORS_FOO:-}"
    f() { setError foo "$@"; }
    f "my error"
    f "my second error"
    f "my third error"
    assert stringContains "my error" "$INSTALL_ERRORS_FOO"
    assert stringContains "my second error" "$INSTALL_ERRORS_FOO"
    assert stringContains "my third error" "$INSTALL_ERRORS_FOO"
}

@test "setError takes input from stdin" {
    assert equal "" "${INSTALL_ERRORS_FOO:-}"
    setError foo - <<< "my error"
    setError foo - <<< "my second error"
    setError foo - <<< "my third error"
    assert stringContains "my error" "$INSTALL_ERRORS_FOO"
    assert stringContains "my second error" "$INSTALL_ERRORS_FOO"
    assert stringContains "my third error" "$INSTALL_ERRORS_FOO"
}
