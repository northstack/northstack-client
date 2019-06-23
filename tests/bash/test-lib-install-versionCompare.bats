#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib-install.sh"

@test "versionCompare returns true if left > right" {
    versionCompare 2 1
    versionCompare 2.0.0.01 2.0.0.001
    versionCompare 2.1b 2.1a
    versionCompare 2.1b 2.1
    versionCompare 2.1.1b 2.1
    versionCompare 2.1.1b 2.1

}

@test "versionCompare returns true if left == right" {
    versionCompare 2 2
    versionCompare 2 2.0
    versionCompare 2a 2a.0
    versionCompare 100 100.0.0.0
}

@test "versionCompare returns false if left < right" {
    ! versionCompare 2 3
    ! versionCompare 2.1 2.2
    ! versionCompare 2.1a 2.2b
    ! versionCompare 32.5 32.5dev
}
