#!/usr/bin/env bats

load helpers

listFuncs() {
    local file=$1
    # Really really un-fun trying to do this programmatically, so let's just mash it
    sed_r -n -e 's/^([a-zA-Z0-9_:]+)[ ]*\(\).*/\1/p' < "$file"
}

getCoverage() {
    local path=$1

    local count=0
    local covered=0
    local REPORT=$(mktemp)
    local stub=$(basename "$path")
    stub=${stub%.sh}
    echo "Missing:" > "$REPORT"
    for func in $(listFuncs $path); do
        count=$((count +1))
        testFile="${BATS_TEST_DIRNAME}/test-${stub}-${func}.bats"
        if [[ -f $testFile ]]; then
            covered=$((covered +1))
        else
            echo "$func $(basename "$testFile")"
        fi
    done >> "$REPORT"

    echo "Total functions: $count" >> "$REPORT"
    echo "Total covered:   $covered" >> "$REPORT"

    printf "Coverage Percent: " >> "$REPORT"
    bc <<< "scale=2; ($covered / $count ) * 100" >> "$REPORT"

    printf "$REPORT"
}

@test "We have coverage for all functions in lib.sh" {
    #if [[ $OSTYPE =~ "darwin" ]]; then skip; fi
    report=$(getCoverage "${BIN_DIR}/lib.sh")
    cat "$report"
    coverage=$(awk '/^Coverage Percent:/ {print $3}' < "$report")
    assert atLeast "$coverage" 75
}

@test "We have coverage for all functions in lib-install.sh" {
    #if [[ $OSTYPE =~ "darwin" ]]; then skip; fi
    report=$(getCoverage "${BIN_DIR}/lib-install.sh")
    cat "$report"
    coverage=$(awk '/^Coverage Percent:/ {print $3}' < "$report")
    assert atLeast "$coverage" 75
}
