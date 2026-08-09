FROM composer:2.10.2 AS composer

FROM php:8.5.9-cli-bookworm AS development

RUN apt-get update \
    && apt-get install --yes --no-install-recommends libpq-dev unzip \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/local/bin/composer

WORKDIR /app

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
