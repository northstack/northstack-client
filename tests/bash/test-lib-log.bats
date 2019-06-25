#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib.sh"

@test "log does pretty much what you would expect it to do" {
    run log "hi there"
    assert stringContains "hi there" "$output"
}

@test "log takes input on stdin" {
    run log - <<< "hi there"
    assert stringContains "hi there" "$output"
}

@test "log prints each argument on its own line" {
    run log "hi there" "and me too"
    assert stringContains "hi there" "${lines[0]}"
    assert stringContains "and me too" "${lines[1]}"
}

@test "log uses info as the default level, but we can change that" {
    run log - <<< "hi there"
    assert stringContains "[info]" "$output"

    run log error "hi there"
    assert stringContains "[error]" "$output"
}
