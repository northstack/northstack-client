#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib.sh"

setup() {
    if [[ -e /tmp/src ]]; then
        rm -r /tmp/src
    fi

    mkdir /tmp/src
    srcFile=$(mkRandomFile /tmp/src)
    srcFilename=$(basename "$srcFile")
    srcDir=/tmp/src

    if [[ -e /tmp/dest ]]; then
        rm -r /tmp/dest
    fi
    mkdir /tmp/dest
    destDir=/tmp/dest
}

teardown() {
    rm -r /tmp/src /tmp/dest
}


@test "Copy a directory tree" {
    srcTree=$(mkRandomTree)
    destTree=$(mktemp -d)/new
    copyTree "$srcTree" "$destTree"
    assert sameFileTree "$srcTree" "$destTree"
}

@test "Overwriting a directory tree" {
    srcTree=$(mkRandomTree /tmp/src)
    destTree=$(mktemp -d -p /tmp/dest)
    run copyTree "$srcTree" "$destTree"
    assert stringContains "directory already exists--removing" "$output"
    assert sameFileTree "$srcTree" "$destTree"
}

@test "Overwriting a directory tree with sudo" {
    srcTree=$(mkRandomTree /tmp/src)
    destTree=$(mktemp -d -p /tmp/dest)
    sudo chown -R root:root "$destTree"
    # try once and expect failure
    run copyTree "$srcTree" "$destTree" < /dev/null
    assert not equal 0 "$status"
    assert not sameFileTree "$srcTree" "$destTree"
    assert stringContains "directory already exists--removing" "$output"

    # now give permission for sudo
    NORTHSTACK_ALLOW_SUDO=1 copyTree "$srcTree" "$destTree"
    assert sameFileTree "$srcTree" "$destTree"
    assert stringContains "directory already exists--removing" "$output"
}

@test "Copying a directory tree removes dangling files in the destination" {
    srcTree=$(mkRandomTree)
    destTree=$(mktemp -d)
    copyTree "$srcTree" "$destTree"
    assert sameFileTree "$srcTree" "$destTree"
    dir=$(find "$destTree" -mindepth 2 -type d | shuf | head -1)
    mkRandomFile "$dir"
    assert not sameFileTree "$srcTree" "$destTree"
    run copyTree "$srcTree" "$destTree"
    assert stringContains "directory already exists--removing" "$output"
    assert sameFileTree "$srcTree" "$destTree"
}

@test "Trying to copy a file fails" {
    run copyTree "$srcFile" "$destDir"
    assert equal 1 "$status"
    assert stringContains "does not exist or is not a directory" "$output"
}

@test "Trying to copy to an unsafe path fails" {
    srcTree=$(mkRandomTree)
    export NORTHSTACK_ALLOW_SUDO=1
    assert not sameFileTree "$srcTree" "/root/private"
    run copyTree "$srcTree" "/root/private"
    assert not sameFileTree "$srcTree" "/root/private"
    assert equal 1 "$status"
    assert stringContains "Refusing to act on unsafe path" "$output"
}
