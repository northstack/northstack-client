#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib.sh"

@test "dockerSocket checks common docker socket paths" {
    skip
    sudo socat -v unix-listen:/var/lib/docker.sock stdout &
    pid=$!
    assert equal /var/lib/docker.sock "$(dockerSocket)"
    sudo kill "$pid"

    run dockerSocket
    assert equal 1 "$status"
    assert stringContains "No docker control socket found" "$output"
}
