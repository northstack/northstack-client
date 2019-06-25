#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib-install.sh"

setup() {
    mkdir -p /tmp/bin /tmp/run
    echo '#!/bin/bash' > /tmp/bin/fake
    echo 'echo -n "$@" > "/tmp/run/$(basename $0)"' >> /tmp/bin/fake
    chmod +x /tmp/bin/fake

    echo '#!/bin/bash' > /tmp/bin/err
    echo 'echo nooooo; exit 1' >> /tmp/bin/err
    chmod +x /tmp/bin/err

    export PATH=/tmp/bin:$PATH
}


teardown() {
    rm -r /tmp/bin /tmp/run
}

@test "getVersion checks php and docker versions correctly" {
    ln -s fake /tmp/bin/php
    getVersion php _

    assert fileExists "/tmp/run/php"
    assert stringContains phpversion "$(< "/tmp/run/php")"


    ln -s fake /tmp/bin/docker
    getVersion docker _

    assert fileExists "/tmp/run/docker"
    assert stringContains 'info --format' "$(< "/tmp/run/docker")"
}


@test "getVersion throws an error for anything unknown" {
    run getVersion NOPE _
    assert equal 1 $status
    assert stringContains "Can't get version for NOPE" "$output"
}

@test "getVersion logs an error if anything returns nonzero" {
    ln -s err /tmp/bin/docker
    run getVersion docker _
    assert stringContains "Failed to check the installed version" "$output"
    assert equal 1 $status
}
