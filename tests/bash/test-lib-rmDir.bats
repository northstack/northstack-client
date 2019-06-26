#!/usr/bin/env bats

source "$BIN_DIR/lib.sh"

load helpers

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
    dir=$(mktemp -d)
    mkdir -p "$dir/foo"
    _sudo chown -R root "$dir"
    assert dirExists "$dir"
    run rmDir "$dir/foo" < /dev/null
    assert not equal 0 "$status"
    run assert dirExists "$dir/foo"
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
