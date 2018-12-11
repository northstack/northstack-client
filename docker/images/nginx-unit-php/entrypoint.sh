#!/bin/sh

set -eu


if getent group "$NORTHSTACK_GID" > /dev/null; then
    grp=$(getent group "$NORTHSTACK_GID" | cut -d: -f 1)
    echo "Remvoing duplicate group $grp with gid $NORTHSTACK_GID"
    groupdel -f "$grp"
fi

echo "Adding group $NORTHSTACK_GROUP"
groupadd \
    --force \
    --gid "$NORTHSTACK_GID" \
    "$NORTHSTACK_GROUP"

echo "Adding user $NORTHSTACK_USER"
useradd \
    --uid "$NORTHSTACK_UID" \
    --no-create-home \
    --key MAIL_DIR=/tmp \
    --gid "$NORTHSTACK_GID" \
    "$NORTHSTACK_USER"

sed \
    -e "s/NORTHSTACK_USERNAME/$NORTHSTACK_USER/g" \
    -e "s/NORTHSTACK_GROUP/$NORTHSTACK_GROUP/g" \
    /unit.json.template \
> /usr/local/unit-state/conf.json

cat /usr/local/unit-state/conf.json


waitFor=/app/public/index.php
max=10
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
