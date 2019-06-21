#!/usr/bin/env bats

load helpers
source "$BIN_DIR/lib.sh"

setup() {
    mkdir -v "$BATS_TMPDIR/tmp"
}

teardown() {
    rm -rvf "$BATS_TMPDIR/tmp"
}


@test "mkdirP can make a new directory" {
    mkdirP "$BATS_TMPDIR/tmp/foo"
    assert dirExists $BATS_TMPDIR/tmp/foo
}

@test "mkdirP works if the directory already exists" {
    mkdir "$BATS_TMPDIR/tmp/foo"
    assert dirExists "$BATS_TMPDIR/tmp/foo"

    mkdirP "$BATS_TMPDIR/tmp/foo"
    assert dirExists "$BATS_TMPDIR/tmp/foo"
}

@test "mkdirP can create nested directories" {
    mkdirP "$BATS_TMPDIR/tmp/foo/bar/baz"
    assert dirExists "$BATS_TMPDIR/tmp/foo/bar/baz"
}

@test "mkdirP handles spaces" {
    mkdirP "$BATS_TMPDIR/tmp/foo/space   here/bar/baz"
    assert dirExists "$BATS_TMPDIR/tmp/foo/space   here/bar/baz"
}
