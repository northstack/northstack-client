#!/bin/sh

set -eu

sed \
    -e "s/NORTHSTACK_USERNAME/$NORTHSTACK_USER/g" \
    -e "s/NORTHSTACK_GROUP/$NORTHSTACK_GROUP/g" \
    /unit.json.template \
> /usr/local/unit-state/conf.json

cat /usr/local/unit-state/conf.json

exec "$@"
