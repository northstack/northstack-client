#!/usr/bin/env bash
set -eu

CDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
BASE="$( dirname "$CDIR")"

. "$CDIR"/lib.sh
. "$CDIR"/lib-install.sh

NS_RED='\033[0;31m'
NS_NC='\033[0m' # No Color
echo ""
echo "NorthStack Client Installer"
echo ""
echo "||========================||"
echo -e "||                    ${NS_RED}.${NS_NC}   ||"
echo -e "||                 ${NS_RED}φ▒╬▌${NS_NC}   ||"
echo -e "||   ▓▓▓▓▓▓▓▓▓▓▀${NS_RED},#╬╬╬╬▌${NS_NC}   ||"
echo -e "||   ▓▓▓▓▓▓▓▓╨${NS_RED}╓╣╬╬╬╬╬╬▌${NS_NC}   ||"
echo -e "||   ▓▓▓▓▓▀╙${NS_RED}╓▒╬╬╬╬╬╬╬╬▌${NS_NC}   ||"
echo -e "||   ▓▓▓▀ ${NS_RED}╔▒╬╬╬╬╬╬╬╬╬╬▌${NS_NC}   ||"
echo -e "||   ▓▀${NS_RED}.φ▒╬╬╬╬╬╬╬╬╬╬╬╬▌${NS_NC}   ||"
echo "||                        ||"
echo "||========================||"
echo ""



if [[ ${0##*/} == "install-dev.sh" ]]; then
    isDev=1
else
    isDev=0
fi

install "$BASE" "$isDev"

