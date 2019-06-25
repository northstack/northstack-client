#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib-install.sh"

@test "doDockerInstall builds a docker image and creates a wrapper script" {
    install=$(mktemp -d)
    src=$(mktemp -d)
    export INSTALL_PATH=$install
    mock docker
    run doDockerInstall "$src"
    assert wasCalled docker
    assert wasCalledWith docker "docker build"
}
