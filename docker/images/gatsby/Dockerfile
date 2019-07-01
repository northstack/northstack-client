FROM node:12-alpine

EXPOSE 8000 9929 9230

ARG APP_APP
ARG APP_ROOT

WORKDIR $APP_APP

EXPOSE 8000

#RUN apk update && \
#    apk add git && \
#    apk add python && \
#    rm -rf /var/cache/apk/*

RUN apk add --no-cache python make g++ \
 && apk add git && \
 && apk add vips-dev fftw-dev build-base \
        --update-cache \
        --repository https://alpine.global.ssl.fastly.net/alpine/edge/community \
        --repository https://alpine.global.ssl.fastly.net/alpine/edge/main \
 && rm -fR /var/cache/apk/*

RUN npm install -g gatsby-cli yarn

ENTRYPOINT ["/bin/sh", "-c"]

CMD ["/gatsby-init"]
