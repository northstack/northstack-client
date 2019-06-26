#!/usr/bin/env bats

source "$BIN_DIR/lib-install.sh"

load helpers

@test "selectInstallMethod returns none if we can't install anything" {
    mockAbsent docker
    mockAbsent php
    selectInstallMethod
    assert equal "none" $INSTALL_METHOD
}
