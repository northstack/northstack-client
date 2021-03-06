#!/bin/sh

set -eu

localIdExists() {
    local id=$1
    local ns=$2

    getent "$ns" "$id" &> /dev/null
    return $?
}

getLocalName() {
    local id=$1
    local ns=$2
    getent "$ns" "$id" | cut -d: -f 1
}

deleteLocal() {
    local name=$1
    local ns=$2

    if [[ $ns == "group" ]]; then
        groupdel -f "$name"
    elif [[ $ns == "passwd" ]]; then
        userdel "$name"
    fi
}

addLocal() {
    local name=$1
    local id=$2
    local ns=$3
    local extra=${4:-}

    if [[ $ns == "group" ]]; then
        echo "Adding group $id"
        groupadd \
            --force \
            --gid "$id" \
            "$name"
    elif [[ $ns == "passwd" ]]; then
        echo "Adding user $id"
        useradd \
            --uid "$id" \
            --no-create-home \
            --key MAIL_DIR=/tmp \
            "$name"
    else
        echo "$ns id $id already exists"
    fi
}

addIfNotExists() {
    local name=$1
    local id=$2
    local ns=$3
    local extra=${4:-}

    if localIdExists "$id" "$ns"; then
        local current=$(getLocalName "$id" "$ns")
        if [[ $name == $current ]]; then
            # desired entity already exists
            return 0
        fi
        # we have another entity using our desired id
        deleteLocal "$current" "$ns"
    fi

    addLocal "$name" "$id" "$ns" "$extra"
}

    echo "Checking for group $NORTHSTACK_GID"
addIfNotExists "ns" "$NORTHSTACK_GID" group
    echo "Checking for user $NORTHSTACK_UID"
addIfNotExists "ns" "$NORTHSTACK_UID" passwd "$NORTHSTACK_GID"

sed \
    -e "s/NORTHSTACK_USERNAME/ns/g" \
    -e "s/NORTHSTACK_GROUP/ns/g" \
    /unit.json.template \
> /usr/local/unit-state/conf.json

cat /usr/local/unit-state/conf.json


waitFor=/app/public/index.php
max=30
tries=0
while [[ ! -f $waitFor ]] && [[ $tries -lt $max ]]; do
    sleep 1
    tries=$(( tries + 1))
done

[[ -f $waitFor ]] || {
    echo "Timeout reached waiting for file to exist: $waitFor"
    exit 1;
}

exec "$@"
