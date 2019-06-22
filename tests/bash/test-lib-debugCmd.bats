#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib.sh"

@test "debugCmd runs a command" {
    run debugCmd echo "hi"
    assert equal 0 "$status"
    assert stringContains "echo hi" "${lines[1]}"
}

@test "debugCmd displays the output when DEBUG=1" {
    f=$(mktemp)
    v=$RANDOM
    echo "$v" > "$f"
    export DEBUG=1
    run debugCmd cat "$f"
    assert equal 0 "$status"
    assert stringContains "$v" "$output"
}

@test "debugCmd displays the output when a command exits non-zero" {
    run debugCmd definitelyNotACommand
    assert stringContains "command not found" "$output"
    assert stringContains "Command returned non-zero" "$output"
    assert not equal 0 "$status"
}

@test "debugCmd properly handles quoted arguments" {
    f=$' some file\nwith\tweird chars'
    run debugCmd touch "/tmp/$f"
    assert equal 0 "$status"
    assert fileExists "/tmp/$f"
}
