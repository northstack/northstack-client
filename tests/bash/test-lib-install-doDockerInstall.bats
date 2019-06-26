#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib-install.sh"

@test "doDockerInstall builds a docker image and creates a wrapper script" {
    install=$(mktemp -d)
    src=$(dirname "$BIN_DIR")
    export INSTALL_PATH=$install
    mock docker
    echo "$install"
    export NON_INTERACTIVE=1
    doDockerInstall "$src"
    assert wasCalled docker
    assert wasCalledWith docker "docker build"
}
