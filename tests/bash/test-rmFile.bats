#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib.sh"

@test "rmFile removes a file" {
    file=$(mktemp)
    assert fileExists "$file"
    rmFile "$file"
    assert not fileExists "$file"
}

@test "rmFile works okay if the file doesn't exist" {
    file=$(mktemp -d)/$RANDOM
    assert not fileExists "$file"
    rmFile "$file"
}

@test "rmFile fails if it doesn't have perms and we don't allow sudo" {
    file=$(sudo mktemp)
    assert fileExists "$file"
    run rmFile "$file" < /dev/null
    assert not equal 0 "$status"
    assert fileExists "$file"
}
