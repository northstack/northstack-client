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
    sudo rm -r /tmp/src /tmp/dest
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
    assert not dirExists /tmp/dest/new
    lnS "$srcFile" /tmp/dest/new/file
    assert dirExists /tmp/dest/new
    assert symlinked "$srcFile" /tmp/dest/new/file
}


@test "symlink in a non-writeable directory" {
    mkdir -p /tmp/dest/new
    sudo chown root:root /tmp/dest/new
    run lnS "$srcFile" /tmp/dest/new/file
    assert not symlinked "$srcFile" /tmp/dest/new/file
    assert not equal 0 $status

    export NORTHSTACK_ALLOW_SUDO=1
    run lnS "$srcFile" /tmp/dest/new/file
    assert symlinked "$srcFile" /tmp/dest/new/file
    assert equal 0 $status
}

@test "symlink a file over an existing non-writeable symlink" {
    lnS "$srcFile" "$destDir"/new
    assert symlinked "$srcFile" "$destDir"/new

    sudo chown root:root "$destDir" "$destDir/new"

    local new=$(mktemp)
    run lnS "$new" "$destDir"/new
    assert not symlinked "$new" "$destDir"/new
    assert not equal 0 $status

    export NORTHSTACK_ALLOW_SUDO=1
    run lnS "$new" "$destDir"/new
    assert symlinked "$new" "$destDir"/new
    assert equal 0 $status
}

@test "symlink is a noop if the link is already correct" {
    lnS "$srcFile" "$destDir"/new
    assert symlinked "$srcFile" "$destDir"/new

    inodeBefore=$(stat -c "%i" "$destDir/new")

    sudo chown root "$destDir/new"
    run lnS "$srcFile" "$destDir"/new

    inodeAfter=$(stat -c "%i" "$destDir/new")

    assert equal "$inodeBefore" "$inodeAfter"
    assert symlinked "$srcFile" "$destDir"/new
    assert equal 0 $status
    assert equal "" "$output"
}


