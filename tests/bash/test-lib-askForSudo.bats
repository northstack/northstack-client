#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib.sh"

@test "We can ask the user for permission to run commands with sudo" {
    run askForSudo "rm foo"
    assert equal 2 "$status"
}

@test "Answering positively at the prompt allows execution to continue" {
    export ASK_FORCE_INTERACTIVE=1
    run askForSudo "rm foo" <<< "yes"
    assert equal 0 "$status"
    assert stringContains "Asking permission to run the following command with sudo:" "${lines[0]}"
    assert equal "rm foo" "${lines[1]}"
}

@test "Answering negatively at the prompt causes failure" {
    export ASK_FORCE_INTERACTIVE=1
    run askForSudo "rm foo" <<< "NAH"
    assert equal 1 "$status"
}

@test "We can override this behavior by setting an environment variable" {
    NORTHSTACK_ALLOW_SUDO=1 run askForSudo "rm foo" < /dev/null
    assert equal 0 "$status"
}
