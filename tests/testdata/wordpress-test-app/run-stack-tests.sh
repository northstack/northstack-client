#!/usr/bin/env bash

set -eu
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

    if [[ $left != $right ]]; then
        local msg=$(printf "'%s' != '%s'\n" "$left" "$right")
        fail "$msg"
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

function appId() {
    jq -r .prod < config/environment.json
}

function setMySQLIP() {
    local app=$(appId)
    local mysqlId=$(docker container ls \
        --filter "label=com.northstack.app.id=$app" \
        --filter "label=com.northstack.localdev.role=mysql" \
        --format '{{ .ID }}')

    local longId=$(docker container inspect "$mysqlId" --format '{{ .ID }}')
    docker network connect bridge "$mysqlId"

    local cidr=$(docker network inspect bridge --format '{{ json .Containers }}' | jq -r --arg c "$longId" '.[$c].IPv4Address')

    MYSQL_IP=${cidr%/*}
}

function sed_i() {
    if [[ $OSTYPE =~ darwin ]]; then
        sed -i '' $@
    else
        sed -i'' $@
    fi
}

patch_env_for_docker() {
    if [[ ${RUNNING_IN_DOCKER:-0} == 1 ]]; then
        setMySQLIP
        NET_ID=$(docker network ls --filter=label=com.docker.compose.network=northstack_local_dev --format '{{ .ID }}')
        NET_GW=$(docker network inspect "$NET_ID" --format '{{ json .IPAM.Config }}' | jq '.[0].Gateway' -r)
        NORTHSTACK_USER=$NORTHSTACK_USER
    else
        MYSQL_IP=127.0.0.1
        NET_GW=127.0.0.1
        NORTHSTACK_USER=$(id -un)
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

patch_env_for_docker

echo the app is instantiated
{
    assertFile ./app/public/wp-cli.yml
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
    sed_i -e "s/127.0.0.1/${MYSQL_IP}/g" wp-config.php
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
