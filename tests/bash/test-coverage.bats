#!/usr/bin/env bats

load helpers

listFuncs() {
    local file=$1
    # Really really un-fun trying to do this programmatically, so let's just mash it
    sed -n -r -e 's/^([a-zA-Z0-9_:]+)[ ]*\(\).*/\1/p' < "$file"
}

getCoverage() {
    local path=$1

    local count=0
    local covered=0
    local report=$(mktemp)
    local stub=$(basename "$path")
    stub=${stub%.sh}
    echo "Missing:" >&3
    for func in $(listFuncs $path); do
        count=$((count +1))
        testFile="${BATS_TEST_DIRNAME}/test-${stub}-${func}.bats"
        if [[ -f $testFile ]]; then
            covered=$((covered +1))
        else
            echo "$func $(basename "$testFile")"
        fi
    done > "$report"
    sort "$report" | column -t >&3

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
