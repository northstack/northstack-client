#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib.sh"

@test "ask exits prematurely if no interactive shell is detected" {
    run ask "how about this?" < /dev/null
    assert equal $status 2
    assert equal "" "$output"
}

@test "ask will still prompt with a special environment variable" {
    export ASK_FORCE_INTERACTIVE=1
    run ask "how about this?" <<< "yes"
    assert equal $status 0
    assert equal "how about this?" "${lines[0]}"
}

@test "ask prompts can contain newlines" {
    export ASK_FORCE_INTERACTIVE=1
    run ask "how about this?\nand this?" <<< "yes"
    assert equal $status 0
    assert equal "how about this?" "${lines[0]}"
    assert equal "and this?" "${lines[1]}"
}

@test "ask defaults to no" {
    export ASK_FORCE_INTERACTIVE=1
    run ask "how about this?\nand this?" <<< "WAT"
    assert equal $status 1
}

@test "ask allows the default to be overriden to yes" {
    export ASK_FORCE_INTERACTIVE=1
    run ask "how about this?\nand this?" "yes" <<< "WAT"
    assert equal $status 0
}
