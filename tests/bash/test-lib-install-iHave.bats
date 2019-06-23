#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib.sh"

@test "iHave returns 0 if \$cmd is in the path" {
    run iHave bash
    assert equal 0 $status
}

@test "iHave returns 1 if \$cmd is not in the path" {
    run iHave NOPE
    assert equal 1 $status
    assert equal "" "$output"
}
