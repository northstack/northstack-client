#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib.sh"

@test "debug displays text to stderr when the DEBUG env var equals 1" {
    run debug "hi there"
    assert equal "" "$output"

    export DEBUG=nope
    run debug "hi there"
    assert equal "" "$output"

    export DEBUG=1
    run debug "hi there"
    assert stringContains "hi there" "$output"
}

@test "debug reads text from stdin if the first param is '-'" {
    export DEBUG=1
    run debug - <<< "stdin here"
    assert stringContains "stdin here" "$output"
}
