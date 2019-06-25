#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib-install.sh"

@test "selectInstallMethod returns none if we can't install anything" {
    selectInstallMethod
    assert equal "none" $INSTALL_METHOD
}

@test "selectInstallMethod can be overridden by setting INSTALL_METHOD" {
    export INSTALL_METHOD=native
    selectInstallMethod
    assert equal "native" $INSTALL_METHOD
}
