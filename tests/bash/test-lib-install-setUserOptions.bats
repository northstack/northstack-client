#!/usr/bin/env bats

source "$BIN_DIR/lib-install.sh"

load helpers

@test "setUserOptions defaults updates ~/northstack-settings.json" {
    setUserOptions
    assert dirExists "$INSTALL_APPDIR"
    assert fileExists "$HOME/.northstack-settings.json"
    run cat "$HOME/.northstack-settings.json"
    assert stringContains "$INSTALL_APPDIR" "$output"
}

@test "setUserOptions won't clobber an existing file" {
    setUserOptions
    _INSTALL_APPDIR=$INSTALL_APPDIR
    export INSTALL_APPDIR=$HOME/northstack/new-apps
    setUserOptions
    assert fileExists "$HOME/.northstack-settings.json"
    run cat "$HOME/.northstack-settings.json"
    assert not stringContains "new-apps" "$output"
}
