#!/usr/bin/env bats

source "$BIN_DIR/lib.sh"

load helpers

@test "The global tmp dir is safe" {
    unset TMPDIR
    assertSafePath "/tmp"
}

@test "The user's TMPDIR is safe" {
    assertSafePath "$TMPDIR/custom-tmp/foo/bar.txt"
}

@test "Known paths in the install prefix are safe" {
    export DEBUG=1
    export INSTALL_PREFIX=$HOME/.local
    try=(
        "$INSTALL_PREFIX/bin/northstack"
        "$INSTALL_PREFIX/northstack"
        "$INSTALL_PREFIX/northstack/some/subdir"
    )
    for p in "${try[@]}"; do
        run assertSafePath "$p"
        printf "%s -> %s\n" "$p" "$status"
        echo "$output"
        assert equal 0 "$status"
    done
}

@test "Other paths are unsafe" {
    try=(
        /
        /home
        "$HOME"
        /var/log
        /var/northstack
        "*/northstack"
    )
    for p in "${try[@]}"; do
        run assertSafePath "$p"
        printf "%s -> %s\n" "$p" "$status"
        assert equal 1 "$status"
    done

}

@test "Relative paths are resolved" {
    skip "We can't really make this work in a portable way yet"
}


@test "Not providing a path yields an error" {
    run assertSafePath
    assert stringContains "path required" "$output"
    assert equal 1 "$status"
}
