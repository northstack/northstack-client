#!/usr/bin/env bash

set -eu

NET_ID=$(docker network ls --filter=label=com.docker.compose.network=northstack_local_dev --format '{{ .ID }}')
NET_GW=$(docker network inspect "$NET_ID" --format '{{ json .IPAM.Config }}' | jq '.[0].Gateway' -r)

fail() {
    local red=$'\e[1;91m'
    local end=$'\e[0m'

    local func=${FUNCNAME[1]}

    printf "${red}${func} FAIL:${end} %s\n" "$@"
}

pass() {
    local green=$'\e[1;32m'
    local end=$'\e[0m'

    local func=${FUNCNAME[1]}

    printf "${green}${func} PASS:${end} %s\n" "$@"
}

function assertFile() {
    local file=$1
    local maxwait=${2:-20}

    local tries=0
    while [[ ! -e $file ]] && (( tries < maxwait )); do
        echo "Waiting for $file to exist"
        sleep 1
        tries=$((tries++))
    done

    if [[ ! -e $file ]]; then
        fail "timeout reached waiting for $file"
        return 1
    fi

    pass "$file exists"
}

function assertEqual() {
    local left=$1
    local right=$2

    if [[ $left != $right ]]; then
        fail "$left != $right"
        return 1
    fi

    pass "$left == $right"
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

echo the app is instantiated
{
    assertFile $PWD/app/public/index.php
}

echo can we run docker-compose
{
    docker-compose ps
}

echo can we run wp-cli commands against the running docker container
{
    cd app/public
    wp @docker plugin status
    cd -
}

echo can we run wp-cli commands locally too
{
    cd app/public
    sed -i -e "s/127.0.0.1/${NET_GW}/g" wp-config.php
    wp @local plugin status
    cd -
}

echo is wordpress up and running?
{
    assertHttp /
    assertHttp /wp-login.php
}

echo we are overriding the siteurl correctly
{
    cd app/public
    url=$(wp @docker option get siteurl)
    assertEqual "http://localhost:8080" "$url"
    cd -
}

echo permalinks work
{
    cd app/public
    wp @local rewrite structure '/%year%/%monthnum%/%postname%/'
    url=$(wp @docker post list --post__in=1 --field=url)
    url=${url/http:\/\/localhost:8080/}
    assertHttp "$url" 200
    cd -
}

echo my user name is intact
{
    assertEqual "$NORTHSTACK_USER" "$(id -un)"
}
