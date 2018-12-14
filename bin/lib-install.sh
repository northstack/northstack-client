ihave() {
    local name=$1
    which "$name" &> /dev/null
    return $?
}

version_compare() {
    local left=${1:-0}
    local right=${2:-0}

    [[ $left == $right ]] && return 0

    if [[ $left =~ ^[[:digit:]]$ ]] && [[ $right =~ ^[[:digit:]]$ ]]; then
        [[ $left -ge $right ]] && return 0
        return 1
    fi

    local l_part
    local r_part

    if [[ $left =~ [.] ]]; then
        l_part=${left%%.*}
        left=${left#$l_part.}
    else
        l_part=$left
        left=
    fi

    if [[ $right =~ [.] ]]; then
        r_part=${right%%.*}
        right=${right#$r_part.}
    else
        r_part=$right
        right=
    fi

    local l_num=$l_part
    local r_num=$r_part

    while [[ $l_part =~ ^[[:digit:]] ]]; do
        l_part=${l_part:1}
    done
    local l_extra=$l_part
    l_num=${l_num%$l_extra}

    while [[ $r_part =~ ^[[:digit:]] ]]; do
        r_part=${r_part:1}
    done
    local r_extra=$r_part
    r_num=${r_num%$r_extra}

    l_num=${l_num##0}; l_num=${l_num:-0}
    r_num=${r_num##0}; r_num=${r_num:-0}

    if (( l_num > r_num )); then
        return 0
    elif (( l_num >= r_num )) && [[ $l_extra == $r_extra || $l_extra > $r_extra ]]; then
        version_compare "$left" "$right" && return 0
    fi

    return 1
}

ihave_php72() {
    ihave php || return 1
    local version=$(php -r 'echo phpversion();')
    version_compare "$version" 7.2 || return 1
    return 0
}

ihave_docker_17_09() {
    ihave docker || return 1
    local version=$(docker info --format '{{ .ServerVersion }}')
    version_compare "$version" 17.09 || return 1
    return 0
}

native_install_ok() {
    ihave_docker_17_09 || return 1
    ihave_php72 || return 1
    return 0
}

docker_install_ok() {
    ihave_docker_17_09 || return 1
    [[ $OSTYPE =~ linux ]] || return 1
    return 0
}

can_install() {
    (native_install_ok || docker_install_ok) && return 0
    return 0
}

select_install_method() {
    native_install_ok && { echo "native"; return 0; }
    docker_install_ok && { echo "docker"; return 0; }
    echo "none"
    return 1
}

native_install_hint() {
    echo "Docker and PHP 7.2 are required"
}
