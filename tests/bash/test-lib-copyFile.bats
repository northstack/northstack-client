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

@test "Copy a single file in a directory" {
    copyFile "$srcFile" "${srcFile}.copy"
    assert sameFileTree "$srcFile" "${srcFile}.copy"
}

@test "Copy a single file into a new directory" {
    newDir=$(mktemp -p /tmp/dest -d)
    dest="${newDir}/sub/directory/${srcFilename}.copy"
    copyFile "$srcFile" "$dest"
    assert sameFileTree "$srcFile" "$dest"
}

@test "Copy a single file into a directory we don't own" {
    newDir=$(mktemp -d)
    sudo chown -R root:root "$newDir"
    sudo chmod 755 "$newDir"
    dest="${newDir}/${srcFilename}.copy"
    run copyFile "$srcFile" "$dest" < /dev/null
    echo "$output"
    assert not equal "$status" 0
    assert not fileExists "$dest"

    # now give permission for sudo
    NORTHSTACK_ALLOW_SUDO=1 run copyFile "$srcFile" "$dest"
    assert equal "$status" 0
    assert sameFileTree "$srcFile" "$dest"
}

@test "Copy a single file into a _new_ directory we don't own" {
    newDir=$(mktemp -d)
    sudo chown -R root:root "$newDir"
    sudo chmod 755 "$newDir"
    dest="${newDir}/sub/dir spaces/${srcFilename}.copy"
    run copyFile "$srcFile" "$dest" < /dev/null
    assert not equal "$status" 0
    assert not fileExists "$dest"

    # now give permission for sudo
    NORTHSTACK_ALLOW_SUDO=1 run copyFile "$srcFile" "$dest"
    assert equal "$status" 0
    assert sameFileTree "$srcFile" "$dest"
}

@test "Overwrite an existing file" {
    new=$(mktemp)
    echo hi > "$new"
    run diff "$srcFile" "$new"
    assert not equal "$status" 0

    copyFile "$srcFile" "$new"
    assert sameFileTree "$srcFile" "$new"
}

@test "Overwrite an existing file w/ sudo" {
    new=$(mktemp)
    echo hi > "$new"
    sudo chown root:root "$new"
    assert not sameFileTree "$srcFile" "$new"

    export NORTHSTACK_ALLOW_SUDO=1
    copyFile "$srcFile" "$new"
    assert sameFileTree "$srcFile" "$new"
}
