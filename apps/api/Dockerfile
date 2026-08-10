FROM composer:2.10.2 AS composer

FROM php:8.5.9-cli-bookworm AS development

RUN apt-get update \
    && apt-get install --yes --no-install-recommends libicu-dev libpq-dev unzip \
    && docker-php-ext-install intl pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY docker/php/conf.d/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /app

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
