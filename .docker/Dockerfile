# syntax=docker/dockerfile:1

FROM rust:1.85.1-alpine3.20 AS lib-builder

RUN set -xe \
    && apk add --no-cache \
        musl-dev

WORKDIR /build

COPY src ./src
COPY Cargo.lock Cargo.toml ./

RUN RUSTFLAGS="-C target-feature=-crt-static" cargo build --release

FROM php:8.3.19-cli-alpine3.21 AS php-dev

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN set -xe \
    && chmod +x /usr/local/bin/install-php-extensions \
    && sync \
    && install-php-extensions \
        ffi \
        opcache \
        spx

ENV COMPOSER_HOME=/root/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV TIKTOKEN_LIB_PATH=/usr/local/lib

WORKDIR /workspace

COPY .docker/conf.d/php/spx.ini ${PHP_INI_DIR}/conf.d/
COPY --from=composer/composer:2-bin /composer /usr/bin/composer
COPY --from=lib-builder /build/target/release/libtiktoken_php.so /usr/local/lib/libtiktoken_php.so

RUN set -xe \
    && apk add --no-cache \
        git \
        openssh-client \
        unzip
