#!/usr/bin/env bats

load helpers

source "$BIN_DIR/lib-install.sh"

@test "updateBashProfile adds a PATH= entry" {
    rc=$(mktemp)
    run updateBashProfile "/tmp/bin" "$rc"
    assert equal "$status" 0
    grep -q 'PATH=/tmp/bin:' "$rc"
}

@test "updateBashProfile is doesn't clobber existing data" {
    rc=$(mktemp)
    echo "# a comment" >> "$rc"
    run updateBashProfile "/tmp/bin" "$rc"
    assert equal "$status" 0
    grep -q '# a comment' "$rc"
    grep -q 'PATH=/tmp/bin:' "$rc"

    echo "# another comment" >> "$rc"

    run updateBashProfile "/tmp/newbin" "$rc"
    assert equal "$status" 0
    grep -q 'PATH=/tmp/newbin:' "$rc"
    grep -q '# a comment' "$rc"
    grep -q '# another comment' "$rc"
}

@test "updateBashProfile is idempotent" {
    rc=$(mktemp)
    content=$RANDOM
    echo "# random content: $content" >> "$rc"
    updateBashProfile "/tmp/bin" "$rc"
    export DEBUG=1
    run updateBashProfile "/tmp/bin" "$rc"
    assert stringContains "No changes detected" "$output"
}
