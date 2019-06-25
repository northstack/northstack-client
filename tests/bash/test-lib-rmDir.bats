#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib.sh"

@test "rmDir removes a directory" {
    f=$(mktemp -d)
    assert dirExists "$f"
    rmDir "$f"
    assert not dirExists "$f"
}

@test "rmDir works okay if the target doesn't exist" {
    dir=$(mktemp -d)/$RANDOM
    assert not dirExists "$dir"
    rmDir "$dir"
}

@test "rmDir fails if it doesn't have perms" {
    dir=$(sudo mktemp -d)
    assert dirExists "$dir"
    run rmDir "$dir" < /dev/null
    assert not equal 0 "$status"
    assert dirExists "$dir"
}

@test "rmDir bails out on things we shouldn't touch" {
    try=(
        /
        /root/
        /var/log/hi
        "$HOME"
        /home
    )

    for t in "${try[@]}"; do
        run rmDir "$t"
        printf "%s -> %s\n" "$t" "$status"
        assert equal 1 "$status"
    done
}
