#!/usr/bin/env bash
set -eu

BIN_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}")"  && pwd)"
BASE="$( dirname "$BIN_DIR")"

. "$BIN_DIR"/lib.sh
. "$BIN_DIR"/lib-install.sh

showBanner() {
    local NS_RED='\033[0;31m'
    local NS_NC='\033[0m' # No Color
    {
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
    } | log info -
}

usage() {
    echo "usage:

\$ $0  OPTIONS

    -h                 Show this dialog
    -a <path>          Set the app directory       (default = \$HOME/northstack/apps)
    -p <path>          Set the install prefix      (default = \$HOME/.local)
    -m docker|native   Set the install method      (default = auto)
    -d                 Install in dev mode         (default = false)
    -n                 Don't prompt for any input  (default = no)
    -v                 Be verbose                  (default = no)
"
}

while getopts "vdnha:p:m:" opt; do
    case "$opt" in
        h)
            usage
            exit 0
            ;;
        a)
            export INSTALL_APPDIR=${OPTARG%/}
            ;;
        p)
            export INSTALL_PREFIX=${OPTARG%/}
            ;;
        n)
            export NON_INTERACTIVE=1
            ;;
        m)
            method=$(strToLower "$OPTARG")
            export INSTALL_METHOD=$method
            ;;
        d)
            export INSTALL_DEV_MODE=1
            ;;
        v)
            export DEBUG=1
            ;;
        ?)
            usage
            exit 1
            ;;
    esac
done

showBanner
install "$BASE"
