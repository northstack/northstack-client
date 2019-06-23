#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib-install.sh"

setup() {
    docker() { echo -n $MIN_DOCKER_VERSION; }
    php() { echo -n $MIN_PHP_VERSION; }
    export -f docker
    export -f php
}

teardown() {
    export -n docker
    export -n php
}

@test "nativeInstallOK requires php and docker binaries to be installed" {
    unset docker
    unset php
    ! iHave php
    ! iHave docker
    run nativeInstallOK
    assert equal 1 $status
}

@test "nativeInstallOK requires a minimum version of php" {
    run nativeInstallOK
    assert equal 0 $status

    php() { echo -n 7.1; }
    export -f php
    run nativeInstallOK
    assert equal 1 $status
}

@test "nativeInstallOK requires a minimum version of docker" {
    run nativeInstallOK
    assert equal 0 $status

    docker() { echo -n 16.5; }
    export -f docker
    run nativeInstallOK
    assert equal 1 $status
}
