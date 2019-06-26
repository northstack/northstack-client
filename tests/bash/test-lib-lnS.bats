#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib.sh"

setup() {
    srcDir="$BATS_TMPDIR/src"
    mkdir "$srcDir"
    srcFile=$(mkRandomFile "$srcDir")
    srcFilename=$(basename "$srcFile")

    destDir="$BATS_TMPDIR/dest"
    mkdir "$destDir"
}

teardown() {
   _sudo rm -r "$srcDir" "$destDir"
}

@test "symlink a file" {
    lnS "$srcFile" "$destDir"/new
    assert symlinked "$srcFile" "$destDir"/new
}

@test "symlink a file over an existing symlink" {
    lnS "$srcFile" "$destDir"/new
    assert symlinked "$srcFile" "$destDir"/new

    local new=$(mktemp)
    lnS "$new" "$destDir"/new
    assert symlinked "$new" "$destDir"/new
}

@test "symlink in a non-existent directory causes the directory to be created" {
    assert not dirExists "$destDir"/new
    lnS "$srcFile" "$destDir"/new/file
    assert dirExists "$destDir"/new
    assert symlinked "$srcFile" "$destDir"/new/file
}


@test "symlink in a non-writeable directory" {
    mkdir -p "$destDir"/new
   _sudo chown root "$destDir"/new
    run lnS "$srcFile" "$destDir"/new/file
    assert not symlinked "$srcFile" "$destDir"/new/file
    assert not equal 0 $status
}

@test "symlink a file over an existing non-writeable symlink" {
    lnS "$srcFile" "$destDir"/new
    assert symlinked "$srcFile" "$destDir"/new

   _sudo chown root "$destDir" "$destDir/new"

    local new=$(mktemp)
    run lnS "$new" "$destDir"/new
    assert not symlinked "$new" "$destDir"/new
    assert not equal 0 $status
}

@test "symlink is a noop if the link is already correct" {
    lnS "$srcFile" "$destDir"/new
    assert symlinked "$srcFile" "$destDir"/new

    inodeBefore=$(stat -c "%i" "$destDir/new")

   _sudo chown root "$destDir/new"
    run lnS "$srcFile" "$destDir"/new

    inodeAfter=$(stat -c "%i" "$destDir/new")

    assert equal "$inodeBefore" "$inodeAfter"
    assert symlinked "$srcFile" "$destDir"/new
    assert equal 0 $status
    assert equal "" "$output"
}
