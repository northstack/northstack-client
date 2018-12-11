#!/usr/bin/env bash

set -eu

function awaitFile() {
    local file=$1
    local maxwait=${2:-20}

    local tries=0
    while [[ ! -e $file ]] && (( tries < maxwait )); do
        echo "Waiting for $file to exist"
        sleep 1
        tries=$((tries++))
    done

    if [[ ! -e $file ]]; then
        echo "timeout reached waiting for $file"
        return 1
    fi
}

function checkHttp() {
    local uri=$1
    local status=${2:-200}

    local req="http://localhost:8080${uri}"
    local ret=$(curl -s -o /dev/null -w '%{http_code}' "$req")

    if [[ $? -ne 0 ]]; then
        echo "curl returned nonzero: $?"
        return $?
    fi

    echo "Checked: $req"
    echo "Expected: $status"
    echo "Returned: $ret"
    [[ $ret == $status ]] && echo success
}

awaitFile $PWD/app/public/index.php

echo can we run docker-compose
{
    docker-compose ps
}

#echo can we run a wp-cli command
#{
#    cd app/public
#    wp plugin status
#    cd -
#}

echo is wordpress up and running?
{
    checkHttp /
    checkHttp /wp-login.php
}

