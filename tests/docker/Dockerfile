ARG BATS_VERSION=v1.1.0

FROM koalaman/shellcheck AS sc

FROM bats/bats:${BATS_VERSION}

RUN apk add \
        rsync \
        curl \
        sudo \
        bash \
        bc \
        util-linux \
        socat

RUN addgroup -g 9999 -S northstack \
    && adduser -u 9999 -D -S -G northstack northstack \
    && echo 'northstack  ALL=(ALL)       NOPASSWD: ALL' > /etc/sudoers.d/northstack

COPY --from=sc /bin/shellcheck /bin/
COPY --chown=northstack .shellcheckrc /home/northstack/.shellcheckrc

USER northstack
