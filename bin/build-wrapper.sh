#!/usr/bin/env bash

OUT=$1
BASE=$2
DEV_MODE=${3:-0}

FUNCTIONS="$(<"$BASE"/bin/wrapper-lib.sh)"
MAIN="$(<"$BASE"/bin/wrapper-main.sh)"

cat << EOF > "$OUT"
#!/usr/bin/env bash

set -e

DEV_MODE=$DEV_MODE
DEV_SOURCE="$BASE"

$FUNCTIONS

$MAIN

main "\$@"

EOF
