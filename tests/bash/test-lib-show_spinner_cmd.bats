#!/usr/bin/env bats

source "$BIN_DIR/lib.sh"

load helpers

@test "show_spinner_cmd returns the same exit code as the command it runs" {
    shellIsInteractive() { return 0; }
    export -f shellIsInteractive

    ret() { return $1; }
    export -f ret

    run show_spinner_cmd ret 0
    assert equal 0 $status

    run show_spinner_cmd ret 127
    assert equal 127 $status

    run show_spinner_cmd debugCmd ret 1
    assert equal 1 $status
}


@test "show_spinner_cmd doesn't break with commands that try to read from stdin" {
    shellIsInteractive() { return 0; }
    export -f shellIsInteractive

    run show_spinner_cmd cat
    assert equal 0 $status
}

@test "show_spinner_cmd doesn't hide the output of a command" {
    t=$(mktemp)
    echo $RANDOM > "$t"
    run show_spinner_cmd cat "$t"
    assert stringContains "$(< "$t")" "$output"
    assert equal 0 $status
}
