#!/usr/bin/env bats

source "$BIN_DIR/lib-install.sh"

load helpers

@test "doNativeInstall installs composer deps w/ composer" {
    install=$(mktemp -d)
    src=$(dirname "$BIN_DIR")
    export INSTALL_PATH=$install
    export NON_INTERACTIVE=1
    mock composer
    doNativeInstall "$src"
    assert wasCalled composer
}

@test "doNativeInstall installs composer deps w/ docker" {
    install=$(mktemp -d)
    src=$(dirname "$BIN_DIR")
    export INSTALL_PATH=$install
    export NON_INTERACTIVE=1
    mockAbsent composer
    mock docker
    doNativeInstall "$src"
    assert wasCalled docker

}
