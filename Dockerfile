# syntax=docker/dockerfile:1.7

FROM composer:2 AS composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
COPY . .
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

FROM node:20-alpine AS assets
WORKDIR /var/www/html
ENV NODE_ENV=production
COPY package.json package-lock.json* ./
RUN npm ci
COPY --from=composer /var/www/html .
RUN npm run build

FROM php:8.3-fpm AS production
WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
        libicu-dev \
        libonig-dev \
        libxml2-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" intl pdo_mysql bcmath gd opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --from=composer /var/www/html /var/www/html
COPY --from=assets /var/www/html/public/build /var/www/html/public/build

ENV APP_ENV=production \
    APP_DEBUG=0 \
    LOG_CHANNEL=stderr

RUN chown -R www-data:www-data storage bootstrap/cache

USER www-data

EXPOSE 9000

CMD ["php-fpm"]
