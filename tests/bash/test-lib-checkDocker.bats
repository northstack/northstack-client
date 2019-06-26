#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib.sh"

@test "checkDocker requires the docker binary present" {
    if [[ $OSTYPE =~ "darwin" ]]; then skip; fi
    ! command -v docker
    run checkDocker
    assert equal 1 $status
    assert stringContains "Is docker installed?" "$output"
}

@test "checkDocker requires docker info to work" {
    docker() { if [[ $1 == "info" ]]; then return 1; fi; }
    export -f docker
    command -v docker
    run checkDocker
    assert equal 1 $status
    assert stringContains "Is the docker daemon running?" "$output"
}

@test "checkDocker succeeds when everything is happy" {
    docker() { :; }
    export -f docker
    command -v docker
    run checkDocker
    assert equal 0 $status
}
