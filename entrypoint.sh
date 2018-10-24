#!/bin/sh

set -e

cd "$NS_PWD"

exec /usr/local/bin/php /app/bin/northstack "$@"
