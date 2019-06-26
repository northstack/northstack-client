#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib.sh"

@test "parentDir returns the first existing parent of a path" {
    mkdir -p "$BATS_TMPDIR/foo"
    run parentDir "$BATS_TMPDIR"/foo/bar/baz
    assert equal $status 0
    assert equal $output "$BATS_TMPDIR"/foo
}

@test "parentDir works with spaces" {
    mkdir -p "$BATS_TMPDIR/space here/and here"
    run parentDir "$BATS_TMPDIR/space here/and here/bottom/dir space"
    assert equal $status 0
    assert equal "$output" "$BATS_TMPDIR/space here/and here"
}
