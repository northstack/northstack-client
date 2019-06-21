#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib.sh"

@test "The global tmp dir is safe" {
    assertSafePath "/tmp"
}

@test "The user's TMPDIR is safe" {
    TMPDIR=$HOME/custom-tmp assertSafePath "$HOME/custom-tmp/foo/bar.txt"
}

@test "Known paths in the install prefix are safe" {
    try=(
        "$(getInstallPrefix)/bin/northstack"
        "$(getInstallPrefix)/northstack"
        "$(getInstallPrefix)/northstack/some/subdir"
    )
    for p in "${try[@]}"; do
        run assertSafePath "$p"
        printf "%s -> %s\n" "$p" "$status"
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

@test "Not providing a path yields an error" {
    run assertSafePath
    assert stringContains "path required" "$output"
    assert equal 1 "$status"
}
