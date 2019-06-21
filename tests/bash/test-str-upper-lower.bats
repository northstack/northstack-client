#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib.sh"

@test "I can upppercase strings" {
    assert equal 'ABCD EFG' "$(strToUpper 'abcd efg')"
}

@test "I can lowercase strings" {
    assert equal 'hijk lmnop' "$(strToLower 'hijk lmnop')"
}
