FROM php:7-cli-alpine3.8 AS build

# build php embed prep
RUN set -xe \
	&& apk add --no-cache --virtual .build-deps \
		$PHPIZE_DEPS \
		coreutils \
		curl-dev \
		libedit-dev \
		libressl-dev \
		libsodium-dev \
		libxml2-dev \
		sqlite-dev \
        icu-dev \
        argon2-dev \
	\
	&& export CFLAGS="$PHP_CFLAGS" \
		CPPFLAGS="$PHP_CPPFLAGS" \
    LDFLAGS="$PHP_LDFLAGS"

RUN docker-php-source extract

## would really like to not copy paste this from the base php library :-(
## trying to get an embed upstream could be the solution, or a build script to suck this in
# build php build
RUN set -xe \
    && cd /usr/src/php \
    && ./configure \
        --build="$gnuArch" \
        --with-config-file-path="$PHP_INI_DIR" \
        --with-config-file-scan-dir="$PHP_INI_DIR/conf.d" \
        \
        --disable-cgi \
        \
# --enable-ftp is included here because ftp_ssl_connect() needs ftp to be compiled statically (see https://github.com/docker-library/php/issues/236)
        --enable-ftp \
# --enable-mbstring is included here because otherwise there's no way to get pecl to use it properly (see https://github.com/docker-library/php/issues/195)
        --enable-mbstring \
# --enable-mysqlnd is included here because it's harder to compile after the fact than extensions are (since it's a plugin for several extensions, not an extension in itself)
        --enable-mysqlnd \
# https://wiki.php.net/rfc/argon2_password_hash (7.2+)
        --with-password-argon2 \
# https://wiki.php.net/rfc/libsodium
        --with-sodium=shared \
        \
        --with-curl \
        --with-libedit \
        --with-openssl \
        --with-zlib \
        \
        --enable-embed \
    && make \
    && make install

# base simple stuff
RUN docker-php-ext-install iconv mysqli pdo pdo_mysql curl intl bcmath

# gd
RUN apk add --update --no-cache jpeg-dev libwebp-dev libpng-dev zlib-dev
RUN docker-php-ext-install gd

# stuff from pecl
RUN pecl install redis-4.1.1 \
    && docker-php-ext-enable redis

RUN pecl install apcu-5.1.12 \
    && docker-php-ext-enable apcu

# nginx unit stuff
RUN apk add --update --no-cache git curl build-base
RUN cd /tmp/
RUN git clone https://github.com/nginx/unit.git
RUN cd unit \
    && ./configure --modules=lib --state=unit-state \
    && ./configure php --module=php72 \
    && make all \
    && make install DESTDIR=/usr/local/


FROM alpine:3.8
COPY --from=build /usr/local /usr/local

ENV PHP_INI_DIR /usr/local/etc/php

RUN apk add --no-cache --virtual .persistent-deps \
		ca-certificates \
		curl \
		tar \
		xz \
        libressl \
        \
        && runDeps="$( \
        scanelf --needed --nobanner --format '%n#p' --recursive /usr/local \
            | tr ',' '\n' \
            | sort -u \
            | awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
        )" \
        && apk add --no-cache --virtual .php-rundeps $runDeps \
        \
        &&  ln -sf /dev/stderr /usr/local/unit.log

COPY php.d /usr/local/etc/php/conf.d/

VOLUME ["/nginx-unit/"]
WORKDIR /usr/local

COPY unit.json /unit.json.template
COPY entrypoint.sh /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/local/sbin/unitd", "--no-daemon", "--control", "unix:/nginx-unit/control.unit.sock"]
