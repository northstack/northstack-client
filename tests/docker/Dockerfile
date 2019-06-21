ARG BATS_VERSION=v1.1.0
FROM bats/bats:${BATS_VERSION}

RUN apk add \
        rsync \
        curl \
        sudo \
        bash

RUN addgroup -g 9999 -S northstack \
    && adduser -u 9999 -D -S -G northstack northstack \
    && echo 'northstack  ALL=(ALL)       NOPASSWD: ALL' > /etc/sudoers.d/northstack

USER northstack
