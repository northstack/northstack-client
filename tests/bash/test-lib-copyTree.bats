#!/usr/bin/env bats

source "$BIN_DIR/lib.sh"

load helpers

@test "Copy a directory tree" {
    srcTree=$(mkRandomTree "$srcDir")
    copyTree "$srcTree" "$destDir/new"
    assert sameFileTree "$srcTree" "$destDir/new"
}

@test "Overwriting a directory tree" {
    srcTree=$(mkRandomTree "$srcDir")
    mkdir -p "$destDir"
    run copyTree "$srcTree" "$destDir"
    assert equal 0 $status
    assert stringContains "already exists--removing" "$output"
    assert sameFileTree "$srcTree" "$destDir"
}

@test "Overwriting a directory tree we don't own" {
    srcTree=$(mkRandomTree "$srcDir")
    destTree=$(mkRandomTree "$destDir")
    _sudo chown -R root "$destDir"
    # try once and expect failure
    run copyTree "$srcTree" "$destTree" < /dev/null
    assert not equal 0 "$status"
    assert not sameFileTree "$srcTree" "$destTree"
    assert stringContains "already exists--removing" "$output"
}

@test "Copying a directory tree removes dangling files in the destination" {
    srcTree=$(mkRandomTree "$srcDir")
    destTree=$(mkRandomTree "$destDir")
    copyTree "$srcTree" "$destTree"
    assert sameFileTree "$srcTree" "$destTree"
    dir=$(find "$destTree" -mindepth 2 -type d | shuf | head -1)
    mkRandomFile "$dir"
    assert not sameFileTree "$srcTree" "$destTree"
    run copyTree "$srcTree" "$destTree"
    assert stringContains "already exists--removing" "$output"
    assert sameFileTree "$srcTree" "$destTree"
}

@test "Trying to copy a file fails" {
    run copyTree "$srcFile" "$destDir"
    assert equal 1 "$status"
    assert stringContains "does not exist or is not a directory" "$output"
}

@test "Trying to copy to an unsafe path fails" {
    srcTree=$(mkRandomTree "$srcDir")
    assert not sameFileTree "$srcTree" "/root/private"
    run copyTree "$srcTree" "/root/private"
    assert not sameFileTree "$srcTree" "/root/private"
    assert equal 1 "$status"
    assert stringContains "Refusing to act on unsafe path" "$output"
}
