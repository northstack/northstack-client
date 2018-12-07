FROM composer as composer
COPY . /app
RUN composer install --ignore-platform-reqs


FROM php:7.2-cli-alpine3.8

RUN echo "memory_limit=-1" > "$PHP_INI_DIR/conf.d/memory-limit.ini" \
    && echo "date.timezone=${PHP_TIMEZONE:-UTC}" > "$PHP_INI_DIR/conf.d/date_timezone.ini" \
    && apk add --no-cache \
        bash \
        shadow \
    && docker-php-ext-install pcntl

COPY --from=composer /app /app

COPY entrypoint.sh /
RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]

ARG DOCKER_GROUP
ARG DOCKER_GID
RUN groupadd \
        --force \
        --non-unique \
        --system \
        --gid "${DOCKER_GID:?build arg is required}" \
        "${DOCKER_GROUP?:build arg is required}"
