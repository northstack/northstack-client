# shellcheck shell=bash

CLEANUP=()

_sudo() {
    sudo --non-interactive "$@" >&3 || {
        echo "Calling sudo failed"
        echo "Command: $@"
        exit 1
    }
}

setup() {
    HOME_TMP=$(mktemp -d "${BATS_TMPDIR}/home.XXXXXX")
    export HOME=$HOME_TMP
    CLEANUP+=("$HOME_TMP")

    MOCK_ROOT=$(mktemp -d "${BATS_TMPDIR}/mock.XXXXXX")
    export MOCK_ROOT
    mkdir -p "$MOCK_ROOT/bin" "$MOCK_ROOT/data"
    CLEANUP+=("$MOCK_ROOT")

    _TMPDIR=$(mktemp -d "${BATS_TMPDIR}/tmpdir.XXXXXX")
    export TMPDIR=$_TMPDIR
    CLEANUP+=("$_TMPDIR")

    APPDIR=$(mktemp -d "${BATS_TMPDIR}/apps.XXXXXX")
    export INSTALL_APPDIR=$APPDIR
    CLEANUP+=("$APPDIR")

    PREFIX=$(mktemp -d "${BATS_TMPDIR}/prefix.XXXXXX")
    export INSTALL_PREFIX=$PREFIX
    CLEANUP+=("$PREFIX")

    mkdir "$TMPDIR/src"
    export srcDir="$TMPDIR/src"
    export srcFile=$(mkRandomFile "$TMPDIR/src")
    export srcFilename=$(basename "$srcFile")

    mkdir "$TMPDIR/dest"
    export destDir="$TMPDIR/dest"
}

teardown() {
    for p in "${CLEANUP[@]}"; do
        _sudo rm -rfv "${p:?WHAT}"
    done
}

mock() {
    local cmd=$1
    local return=${2:-0}
    local output=${3:-}

    cat <<EOF > "$MOCK_ROOT/bin/$cmd"
#!/bin/bash
touch $MOCK_ROOT/data/${cmd}.called
echo "\$(basename "\$0") \$@" > "$MOCK_ROOT/data/${cmd}.args"

printf "$output"
exit $return
EOF
    chmod +x "$MOCK_ROOT/bin/$cmd"
    if [[ $PATH != *"$MOCK_ROOT/bin"* ]]; then
        export PATH=${MOCK_ROOT}/bin:$PATH
    fi
}

iHave() {
    local cmd=$1
    if [[ -e "$MOCK_ROOT/absent" ]] && grep -q "$cmd" "$MOCK_ROOT/absent"; then
        return 1
    fi
    command -v "$cmd" > /dev/null
}

mockAbsent() {
    local bin=$1
    echo "$bin" >> "$MOCK_ROOT/absent"
    export -f iHave
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
    if _sudo test -f "$file"; then
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
    if _sudo test -d "$file"; then
        echo "\`$file\` exists"
        return 0
    else
        echo "\`$file\` does not exist"
        return 1
    fi
}

assertEqual() {
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

    printf "'%s' == '%s'\n" "$left"  "$right"
    return 0
}

assertHttp() {
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

sed_i() {
    if [[ $OSTYPE =~ darwin ]]; then
        sed -i '' "$@"
    else
        sed -i'' "$@"
    fi
}

sed_r() {
    if [[ $OSTYPE =~ darwin ]]; then
        sed -E "$@"
    else
        sed -r "$@"
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
    local base=${1:-$TMPDIR}
    local file
    file=$(mktemp "${base}/${RANDOM}.XXXXXX") || return 1
    echo -e "$(date)\n${RANDOM}\n${RANDOM}\n${RANDOM}" > "$file"
    echo -n "$file"
}

mkRandomTree() {
    local base=${1:-$TMPDIR}
    local depth=${2:-4}
    local srcDir
    mkdir -p "$base"
    srcDir=$(mktemp -d "$base/$RANDOM.XXXXXX") || return 1
    local subdir=$srcDir
    for _ in $(seq 0 "$depth"); do
        # shellcheck disable=SC2034
        for j in $(seq 0 "$depth"); do
            mkRandomFile "$subdir" > /dev/null || return 1
        done
        subdir="${subdir}/${RANDOM} - ${RANDOM}"
        mkdir -p "$subdir" || return 1
    done
    echo -n "$srcDir"
}

sameFileTree() {
    local left=$1
    local right=$2

    run _sudo diff -r "$left" "$right"
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
