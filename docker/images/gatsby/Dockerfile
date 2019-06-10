FROM node:12-alpine

EXPOSE 8000

RUN apk update && \
    apk add git && \
    apk add python && \
    rm -rf /var/cache/apk/*

RUN npm install --global gatsby --no-optional gatsby@${FRAMEWORK_VERSION}

WORKDIR ${APP_APP}

ENTRYPOINT ["/bin/sh", "-c"]

COPY "gatsby-init" "/gatsby-init"

CMD ["/gatsby-init"]
