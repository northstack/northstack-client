FROM composer as composer
COPY . /app
RUN composer install


FROM php:7.2-cli-alpine3.8

RUN echo "memory_limit=-1" > "$PHP_INI_DIR/conf.d/memory-limit.ini" \
    && echo "date.timezone=${PHP_TIMEZONE:-UTC}" > "$PHP_INI_DIR/conf.d/date_timezone.ini"

COPY --from=composer /app /app
RUN mkdir /current

WORKDIR /current
ENTRYPOINT [ "/usr/local/bin/php", "/app/bin/northstack" ]
