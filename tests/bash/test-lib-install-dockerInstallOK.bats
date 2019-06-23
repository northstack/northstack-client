#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib-install.sh"

@test "dockerInstallOK requires the docker binary" {
    ! iHave docker
    run dockerInstallOK
    assert equal 1 $status
}


@test "nativeInstallOK requires a minimum version of docker" {
    docker() { echo -n 16.5; }
    export -f docker
    run dockerInstallOK
    assert equal 1 $status

    docker() { echo -n $MIN_DOCKER_VERSION; }
    export -f docker
    run dockerInstallOK
    assert equal 0 $status
}
