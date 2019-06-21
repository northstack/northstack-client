#!/usr/bin/env bats

load helpers

listFuncs() {
    local file=$1
    local before=$(mktemp)
    local after=$(mktemp)
    declare -F | sed -e 's/declare -f//' > "$before"
    (. "$file" && declare -F) | sed -e 's/declare -f//'  > "$after"
    while read f; do
        if grep -E -q "$f" "$before"; then
            continue
        fi
        echo "$f"
    done < "$after"
}

getCoverage() {
    local path=$1

    local count=0
    local covered=0
    local report=$(mktemp)
    local stub=$(basename "$path")
    stub=${stub%.sh}
    for func in $(listFuncs $path); do
        count=$((count +1))
        testFile="${BATS_TEST_DIRNAME}/test-${stub}-${func}.bats"
        if [[ -f $testFile ]]; then
            covered=$((covered +1))
        else
            echo "$func $(basename "$testFile")"
        fi
    done > "$report"
    sort "$report" | column -t >&2

    echo "Total functions: $count" >&3
    echo "Total covered:   $covered" >&3

    bc <<< "scale=2; ($covered / $count ) * 100"
}

@test "We have coverage for all functions in lib.sh" {
    coverage=$(getCoverage "${BIN_DIR}/lib.sh")
    assert atLeast "$coverage" 50
}

@test "We have coverage for all functions in lib-install.sh" {
    coverage=$(getCoverage "${BIN_DIR}/lib-install.sh")
    assert atLeast "$coverage" 50
}
