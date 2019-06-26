#!/usr/bin/env bats

source "$BIN_DIR/lib.sh"

load helpers

@test "Copy a single file in a directory" {
    copyFile "$srcFile" "${srcFile}.copy"
    assert sameFileTree "$srcFile" "${srcFile}.copy"
}

@test "Copy a single file into a new directory" {
    newDir=$(mktemp -d)
    dest="${newDir}/sub/directory/${srcFilename}.copy"
    copyFile "$srcFile" "$dest"
    assert sameFileTree "$srcFile" "$dest"
}

@test "Copy a single file into a directory we don't own" {
    newDir=$(mktemp -d)
    _sudo chown -R root "$newDir"
    _sudo chmod 755 "$newDir"
    dest="${newDir}/${srcFilename}.copy"
    run copyFile "$srcFile" "$dest" < /dev/null
    echo "$output"
    assert not equal "$status" 0
    assert not fileExists "$dest"
}

@test "Copy a single file into a _new_ directory we don't own" {
    newDir=$(mktemp -d)
    _sudo chown -R root "$newDir"
    _sudo chmod 755 "$newDir"
    dest="${newDir}/sub/dir spaces/${srcFilename}.copy"
    run copyFile "$srcFile" "$dest" < /dev/null
    assert not equal "$status" 0
    assert not fileExists "$dest"
}

@test "Overwrite an existing file" {
    new=$(mktemp)
    echo hi > "$new"
    run diff "$srcFile" "$new"
    assert not equal "$status" 0

    copyFile "$srcFile" "$new"
    assert sameFileTree "$srcFile" "$new"
}

@test "Trying to copy a directory fails" {
    run copyFile "$srcDir" "$destDir"
    assert not equal 0 "$status"
    assert stringContains "does not exist or is not a regular file" "$output"
}
