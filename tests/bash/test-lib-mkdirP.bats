#!/usr/bin/env bats

load helpers
source "$BIN_DIR/lib.sh"

@test "mkdirP can make a new directory" {
    mkdirP "$TMPDIR/foo"
    assert dirExists "$TMPDIR/foo"
}

@test "mkdirP works if the directory already exists" {
    mkdir "$TMPDIR/foo"
    assert dirExists "$TMPDIR/foo"

    mkdirP "$TMPDIR/foo"
    assert dirExists "$TMPDIR/foo"
}

@test "mkdirP can create nested directories" {
    mkdirP "$TMPDIR/tmp/foo/bar/baz"
    assert dirExists "$TMPDIR/tmp/foo/bar/baz"
}

@test "mkdirP handles spaces" {
    mkdirP "$TMPDIR/tmp/foo/space   here/bar/baz"
    assert dirExists "$TMPDIR/tmp/foo/space   here/bar/baz"
}
