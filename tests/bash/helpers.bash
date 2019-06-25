# shellcheck shell=bash

declare -g CLEANUP=()

setup() {
    export MOCK_ROOT=$(mktemp -d -p ${BATS_TMPDIR})
    mkdir -p "$MOCK_ROOT/bin" "$MOCK_ROOT/data"
    CLEANUP+=("$MOCK_ROOT")
}

teardown() {
    for path in "${CLEANUP[@]}"; do
        rm -rfv "$path"
    done
}

mock() {
    local cmd=$1
    cat <<EOF > "$MOCK_ROOT/bin/$cmd"
#!/bin/bash
touch $MOCK_ROOT/data/${cmd}.called
echo "\$(basename "\$0") \$@" > "$MOCK_ROOT/data/${cmd}.args"
EOF
    chmod +x "$MOCK_ROOT/bin/$cmd"
    if [[ $PATH != *"$MOCK_ROOT/bin"* ]]; then
        export PATH=${MOCK_ROOT}/bin:$PATH
    fi
}

wasCalled() {
    local cmd=$1
    assert fileExists "$MOCK_ROOT/data/${cmd}.called"
}

wasCalledWith() {
    local cmd=$1
    local str=$2
    assert fileExists "$MOCK_ROOT/data/${cmd}.called"
    assert fileExists "$MOCK_ROOT/data/${cmd}.args"
    assert stringContains "$str" "$(< "$MOCK_ROOT/data/${cmd}.args")"
}

fail() {
    local red=$'\e[1;91m'
    local end=$'\e[0m'
    local blue=$'\e[1;34m'

    local func=$1
    shift

    printf "${blue}${func}${end} ${red}FAIL:${end} %s\n" "$@"
}

pass() {
    local green=$'\e[1;32m'
    local end=$'\e[0m'
    local blue=$'\e[1;34m'

    local func=$1
    shift

    printf "${blue}${func}${end} ${green}PASS:${end} %s\n" "$@"
}

fileExists() {
    local file=$1
    if [[ -f $file ]]; then
        echo "\`$file\` exists"
        return 0
    else
        echo "\`$file\` does not exist"
        return 1
    fi
}

symlinked() {
    local target=$1
    local link=$2

    if [[ ! -e $target ]]; then
        echo "Link target ($target) does not exist"
        return 1
    fi

    if [[ ! -h $link ]]; then
        echo "Link ($link) does not exist or is not a symlink"
        return 1
    fi

    local actual=$(readlink "$link")

    if [[ $actual == "$target" ]]; then
        echo "$link -> $target"
        return 0
    fi

    echo "expected: $link -> $target, actual: $link -> $actual"
    return 1
}

dirExists() {
    local file=$1
    if [[ -d $file ]]; then
        echo "\`$file\` exists"
        return 0
    else
        echo "\`$file\` does not exist"
        return 1
    fi
}

function assertEqual() {
    left=$1
    right=$2
    local trim=${3:-1}

    if [[ $trim == 1 ]]; then
        trim left
        trim right
    fi

    if [[ $left != "$right" ]]; then
        local msg=$(printf "'%s' != '%s'\n" "$left" "$right")
        echo "$msg"
        return 1
    fi

    echo "$left == $right"
    return 0
}

function assertHttp() {
    local uri=$1
    local status=${2:-200}

    local req="http://${NET_GW}:8080${uri}"
    local ret=$(curl -s -o /dev/null -w '%{http_code}' "$req")

    if [[ $? -ne 0 ]]; then
        echo "curl returned nonzero: $?"
        return $?
    fi

    assertEqual "$status" "$ret" && pass "$req returned $status"
}

function sed_i() {
    if [[ $OSTYPE =~ darwin ]]; then
        sed -i '' "$@"
    else
        sed -i'' "$@"
    fi
}

trim() {
    local name=$1
    local var=${!name}
    while true; do
        local len=${#var}
        var=${var##$'\r'}
        var=${var%%$'\r'}
        var=${var##$'\n'}
        var=${var%%$'\n'}
        var=${var##$'\t'}
        var=${var%%$'\t'}
        var=${var## }
        var=${var%% }
        if [[ $len -eq ${#var} ]]; then
            break
        fi
    done
    printf -v "$name" "%s" "$var"
}

xor() {
    local left=$1
    local right=$2

    (( left == 0 || right == 0 )) \
    && (( !(left == 0 && right == 0) ))
}

mkRandomFile() {
    local base=${1:-$BATS_TMPDIR}
    local file=$(mktemp -p "$base")
    echo -e "$(date)\n${RANDOM}\n${RANDOM}\n${RANDOM}" > "$file"
    echo -n "$file"
}

mkRandomTree() {
    local base=${1:-$BATS_TMPDIR}
    local depth=${2:-4}
    local srcDir=$(mktemp -d -p "$base")
    local subdir=$srcDir
    for _ in $(seq 0 "$depth"); do
        # shellcheck disable=SC2034
        for j in $(seq 0 "$depth"); do
            mkRandomFile "$subdir" > /dev/null
        done
        subdir="${subdir}/${RANDOM} - ${RANDOM}"
        mkdir -p "$subdir"
    done
    echo -n "$srcDir"
}

sameFileTree() {
    local left=$1
    local right=$2

    run sudo diff -r "$left" "$right"
    assert equal "$output" ""
    assert equal "$status" 0
}

stringContains() {
    local needle=$1
    local haystack=$2
    if [[ $haystack == *$needle* ]]; then
        echo "\`$needle\` is in \`$haystack\`"
        return 0
    fi
    echo "\`$needle\` is not in \`$haystack\`"
    return 1
}

atLeast() {
    local left=$1
    local right=$2

    local prog="scale=2; $left >= $right"
    local result
    result=$(bc <<< "$prog")
    if (( result == 1)); then
        echo "$left >= $right"
        return 0
    else
        echo "$left < $right"
        return 1
    fi
}

assert() {
    local inverse=1
    if [[ $1 == not ]]; then
        inverse=0
        shift
    fi

    local check=$1
    shift

    local func
    case $check in
        equal)
            func=assertEqual
            ;;
        fileExists)
            func=fileExists
            ;;
        dirExists)
            func=dirExists
            ;;
        sameFileTree)
            func=sameFileTree
            ;;
        stringContains)
            func=stringContains
            ;;
        atLeast)
            func=atLeast
            ;;
        symlinked)
            func=symlinked
            ;;
        wasCalled)
            func=wasCalled
            ;;
        wasCalledWith)
            func=wasCalledWith
            ;;
        *)
            fail "Unknown assertion: $check"
            return 1
            ;;
    esac

    set +e
    local output
    local status
    output=$($func "$@")
    status=$?
    set -e

    local name=$func
    if [[ $inverse == 0 ]]; then
        name="not $func"
    fi

    if xor "$inverse" "$status"; then
        pass "$name" "$output"
        return 0
    else
        fail "$name" "$output"
        return 1
    fi
}
