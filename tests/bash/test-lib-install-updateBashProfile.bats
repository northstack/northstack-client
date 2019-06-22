#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib-install.sh"

@test "updateBashProfile doesn't change things without permission" {
    prefix=$(setInstallPrefix)
    touch $HOME/.bashrc
    echo "# $RANDOM" >> $HOME/.bashrc
    cp $HOME/.bashrc{,.bak}
    updateBashProfile "$prefix/bin" "$HOME/.bashrc" < /dev/null
    assert sameFileTree $HOME/.bashrc $HOME/.bashrc.bak
}
