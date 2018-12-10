#!/bin/sh

set -eu

groupadd \
    --force \
    --non-unique \
    --gid "$NORTHSTACK_GID" \
    "$NORTHSTACK_GROUP"

useradd \
    --uid "$NORTHSTACK_UID" \
    --no-create-home \
    --gid "$NORTHSTACK_GID" \
    "$NORTHSTACK_USER"

sed \
    -e "s/NORTHSTACK_USERNAME/$NORTHSTACK_USER/g" \
    -e "s/NORTHSTACK_GROUP/$NORTHSTACK_GROUP/g" \
    /unit.json.template \
> /usr/local/unit-state/conf.json

cat /usr/local/unit-state/conf.json

exec "$@"
