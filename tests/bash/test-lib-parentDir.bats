#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib.sh"

@test "parentDir returns the first existing parent of a path" {
    mkdir -p /tmp/foo/bar
    run parentDir /tmp/foo/bar/baz
    assert equal $status 0
    assert equal $output /tmp/foo/bar
}

@test "parentDir works with spaces" {
    mkdir -p "/tmp/space here/and here"
    run parentDir "/tmp/space here/and here/bottom/dir space"
    assert equal $status 0
    assert equal "$output" "/tmp/space here/and here"
}
