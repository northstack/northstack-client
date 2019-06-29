FROM node:12-alpine

ARG APP_APP
ARG FRAMEWORK_VERSION

#ENV APP_APP $APP_APP
#ENV FRAMEWORK_VERSION $FRAMEWORK_VERSION

WORKDIR $APP_APP

EXPOSE 8000

RUN apk update && \
    apk add git && \
    apk add python && \
    rm -rf /var/cache/apk/*

RUN npm install --global gatsby --no-optional gatsby@$FRAMEWORK_VERSION

ENTRYPOINT ["/bin/sh", "-c"]

COPY "gatsby-init" "/gatsby-init"

CMD ["/gatsby-init"]
