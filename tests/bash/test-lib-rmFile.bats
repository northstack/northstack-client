#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib.sh"

@test "rmFile removes a file" {
    f=$(mktemp)
    assert fileExists "$f"
    rmFile "$f"
    assert not fileExists "$f"
}

@test "rmFile works okay if the file doesn't exist" {
    file=$(mktemp -d)/$RANDOM
    assert not fileExists "$file"
    rmFile "$file"
}

@test "rmFile fails if it doesn't have perms" {
    file=$_sudo mktemp)
    assert fileExists "$file"
    run rmFile "$file" < /dev/null
    assert not equal 0 "$status"
    assert fileExists "$file"
}

@test "rmFile bails out on things we shouldn't touch" {
    try=(
        /
        /root/foo.txt
        /var/log/hi
        "$HOME/.bashrc"
    )

    for t in "${try[@]}"; do
        run rmFile "$t"
        printf "%s -> %s\n" "$t" "$status"
        assert equal 1 "$status"
    done
}
