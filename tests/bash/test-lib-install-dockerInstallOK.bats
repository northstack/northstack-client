#!/usr/bin/env bats

source "$BIN_DIR/lib-install.sh"

load helpers

@test "dockerInstallOK requires the docker binary" {
    mockAbsent docker
    run dockerInstallOK
    assert equal 1 $status
}


@test "nativeInstallOK requires a minimum version of docker" {
    mock docker 0 16.5
    run dockerInstallOK
    assert equal 1 $status

    mock docker 0 $MIN_DOCKER_VERSION
    run dockerInstallOK
    assert equal 0 $status
}
