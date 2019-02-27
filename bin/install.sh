#!/usr/bin/env bash
set -eu

CDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
BASE="$( dirname "$CDIR")"

. "$CDIR"/lib.sh
. "$CDIR"/lib-install.sh

echo ""
echo "NorthStack Client Installer"
echo ""
echo "||========================||"
echo "||                    .   ||"
echo "||                 φ▒╬▌   ||"
echo "||   ▓▓▓▓▓▓▓▓▓▓▀,#╬╬╬╬▌   ||"
echo "||   ▓▓▓▓▓▓▓▓╨╓╣╬╬╬╬╬╬▌   ||"
echo "||   ▓▓▓▓▓▀╙╓▒╬╬╬╬╬╬╬╬▌   ||"
echo "||   ▓▓▓▀ ╔▒╬╬╬╬╬╬╬╬╬╬▌   ||"
echo "||   ▓▀.φ▒╬╬╬╬╬╬╬╬╬╬╬╬▌   ||"
echo "||                        ||"
echo "||========================||"
echo ""



if [[ ${0##*/} == "install-dev.sh" ]]; then
    isDev=1
else
    isDev=0
fi

install "$BASE" "$isDev"

