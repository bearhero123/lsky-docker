# syntax=docker/dockerfile:1.7

FROM composer:2.7 AS vendor
WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --no-scripts

COPY . .
RUN composer dump-autoload \
    --no-dev \
    --optimize \
    --classmap-authoritative \
    --no-interaction

FROM php:8.1-fpm-bookworm

ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libmagickwand-dev \
        libonig-dev \
        libpng-dev \
        libxml2-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        mbstring \
        pcntl \
        pdo_mysql \
        xml \
        zip \
    && pecl install imagick \
    && docker-php-ext-enable imagick opcache \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY docker/app/php.ini /usr/local/etc/php/conf.d/99-lsky.ini
COPY docker/app/entrypoint.sh /usr/local/bin/lsky-entrypoint

RUN chmod +x /usr/local/bin/lsky-entrypoint \
    && mkdir -p storage/app/public \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/testing storage/framework/views \
    && mkdir -p storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

EXPOSE 9000

ENTRYPOINT ["lsky-entrypoint"]
CMD ["php-fpm"]
