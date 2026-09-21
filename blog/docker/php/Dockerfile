FROM php:8.4-fpm AS base

WORKDIR /app

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    libonig-dev \
    && docker-php-ext-install \
    intl \
    pdo \
    pdo_mysql \
    zip \
    opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --link --chmod=755 docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]

ENV COMPOSER_ALLOW_SUPERUSER=1

FROM base AS prod

ENV APP_ENV=prod
ENV APP_DEBUG=0

COPY  composer.* symfony.* ./

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

COPY . .

RUN mkdir -p var/cache var/log \
    && chmod +x bin/console \
    && php bin/console assets:install public

RUN chown -R www-data:www-data /app
USER www-data

CMD ["php-fpm"]

FROM base AS dev

ENV APP_ENV=dev
ENV APP_DEBUG=1

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY composer.* symfony.* ./

COPY . .

RUN composer install \
    --prefer-dist \
    --no-interaction

RUN mkdir -p var/cache var/log \
    && chown -R www-data:www-data var \
    && chmod -R 775 var

CMD ["php-fpm"]