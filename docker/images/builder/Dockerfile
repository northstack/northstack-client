ARG ALPINE_VERSION=3.8
FROM alpine:$ALPINE_VERSION

RUN apk add --update --no-cache \
        bash \
        git

COPY run-user-build /usr/local/bin/

HEALTHCHECK --interval=5s --start-period=1s --timeout=30s --retries=10 \
    CMD "test -f /tmp/.finished || exit 1"

WORKDIR /app
CMD ["/usr/local/bin/run-user-build"]
