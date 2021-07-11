FROM composer:2 AS composer

# Ran into this: https://gitlab.alpinelinux.org/alpine/aports/-/issues/12396
# You can downgrade to alpine 3.13 or do this:
# 1. Make sure Docker is current, e.g. https://docs.docker.com/engine/install/fedora/
# 2. Make sure runc is current. https://github.com/opencontainers/runc/releases
FROM php:7.4-cli-alpine3.14
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Xdebug is used for measuring test coverage
RUN apk add autoconf g++ make
RUN pecl install xdebug && docker-php-ext-enable xdebug
RUN apk del --purge autoconf g++ make
