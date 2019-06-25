#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib-install.sh"

@test "doNativeInstall installs composer deps" {
    install=$(mktemp -d)
    src=$(mktemp -d)
    export INSTALL_PATH=$install

    mock docker
    run doNativeInstall "$src"
    assert wasCalled docker

    mock composer
    run doNativeInstall "$src"
    assert wasCalled composer
}
