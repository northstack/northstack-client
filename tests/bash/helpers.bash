# shellcheck shell=bash

fail() {
    local red=$'\e[1;91m'
    local end=$'\e[0m'
    local blue=$'\e[1;34m'

    local func=$1
    shift

    printf "${blue}${func}${end} ${red}FAIL:${end} %s\n" "$@" >&3
}

pass() {
    local green=$'\e[1;32m'
    local end=$'\e[0m'
    local blue=$'\e[1;34m'

    local func=$1
    shift

    printf "${blue}${func}${end} ${green}PASS:${end} %s\n" "$@" >&3
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
function assertFile() {
    local file=$1
    local maxwait=${2:-20}

    local tries=0
    while [[ ! -e $file ]] && (( tries < maxwait )); do
        echo "Waiting for $file to exist"
        sleep 1
        tries=$((tries + 1))
    done

    if [[ ! -e $file ]]; then
        fail "timeout reached waiting for $file"
        return 1
    fi

    pass "$file exists"
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

function assert() {
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
