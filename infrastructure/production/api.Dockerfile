FROM composer:2.10.2 AS dependencies

FROM php:8.5.9-cli-bookworm AS dependencies-runtime

WORKDIR /app

RUN apt-get update \
    && apt-get install --yes --no-install-recommends libicu-dev libpq-dev unzip \
    && docker-php-ext-install intl pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=dependencies /usr/bin/composer /usr/local/bin/composer

COPY apps/api/composer.json apps/api/composer.lock ./

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --classmap-authoritative

FROM php:8.5.9-fpm-bookworm

RUN apt-get update \
    && apt-get install --yes --no-install-recommends libicu-dev libpq-dev \
    && docker-php-ext-install intl pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY --from=dependencies-runtime /app/vendor ./vendor
COPY apps/api .

RUN rm --force .env .env.dev .env.test \
    && rm --recursive --force tests \
    && mkdir --parents var/cache var/log \
    && chown --recursive www-data:www-data var

USER www-data

ENTRYPOINT ["/bin/sh", "-c", "set -a; . /run/secrets/api_env; set +a; exec php-fpm --nodaemonize"]
