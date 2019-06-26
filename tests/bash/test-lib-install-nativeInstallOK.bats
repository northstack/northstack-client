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
    mock docker 0 $MIN_DOCKER_VERSION
    mock php 0 $MIN_PHP_VERSION
    nativeInstallOK
    showErrors

    mock php 0 '7.1'
    nativeInstallOK || run showErrors
    assert equal 1 $status
    assert stringContains "7.1" "$output"
    assert stringContains "version requirement for php not met" "$output"
}

@test "nativeInstallOK requires a minimum version of docker" {
    mock php 0 $MIN_PHP_VERSION
    mock docker 0 "$MIN_DOCKER_VERSION"
    nativeInstallOK
    showErrors

    mock docker 0 16.5
    nativeInstallOK || run showErrors
    assert equal 1 $status
    assert stringContains "16.5" "$output"
    assert stringContains "version requirement for docker not met" "$output"
}
