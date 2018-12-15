#!/usr/bin/env bash

set -eu

if getent group "$DOCKER_GID" > /dev/null; then
    grp=$(getent group "$DOCKER_GID" | cut -d: -f 1)
    groupdel -f "$grp"
fi


echo "Adding docker group: $DOCKER_GROUP, gid: $DOCKER_GID"
groupadd \
        --force \
        --gid "$DOCKER_GID" \
        "$DOCKER_GROUP" \

if getent group "$NORTHSTACK_GID" > /dev/null; then
    grp=$(getent group "$NORTHSTACK_GID" | cut -d: -f 1)
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
    --gid "$DOCKER_GID" \
    "$NORTHSTACK_USER"

exec sudo \
    --preserve-env \
    --user "$NORTHSTACK_USER" \
    --shell /bin/bash \
    --login \
    "$@"
