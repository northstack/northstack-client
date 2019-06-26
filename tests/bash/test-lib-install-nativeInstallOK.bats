#!/usr/bin/env bats


source "$BIN_DIR/lib-install.sh"

load helpers

@test "nativeInstallOK requires php and docker binaries to be installed" {
    mockAbsent php
    mockAbsent docker
    run nativeInstallOK
    assert equal 1 $status
}

@test "nativeInstallOK requires a minimum version of php" {
    run nativeInstallOK
    assert equal 0 $status

    mock php 0 7.1
    run nativeInstallOK
    assert equal 1 $status
}

@test "nativeInstallOK requires a minimum version of docker" {
    run nativeInstallOK
    assert equal 0 $status

    mock docker 0 16.5
    run nativeInstallOK
    assert equal 1 $status
}
