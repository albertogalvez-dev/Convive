FROM composer:2.10.2 AS dependencies

FROM php:8.5.9-cli-bookworm AS dependencies-runtime

WORKDIR /app

RUN apt-get update \
    && apt-get install --yes --no-install-recommends $PHPIZE_DEPS libicu-dev libpq-dev unzip \
    && curl -fsSL --retry 8 --retry-all-errors --retry-delay 5 --max-time 120 \
        -o /tmp/redis.tgz https://pecl.php.net/get/redis-6.3.0.tgz \
    && echo "0d5141f634bd1db6c1ddcda053d25ecf2c4fc1c395430d534fd3f8d51dd7f0b5  /tmp/redis.tgz" | sha256sum -c - \
    && pecl install /tmp/redis.tgz \
    && rm -f /tmp/redis.tgz \
    && docker-php-ext-enable redis \
    && docker-php-ext-install intl pdo_pgsql \
    && apt-get purge --yes --auto-remove $PHPIZE_DEPS \
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

COPY apps/api .

RUN composer dump-autoload \
    --no-dev \
    --classmap-authoritative \
    --no-scripts

FROM php:8.5.9-fpm-bookworm

RUN apt-get update \
    && apt-get install --yes --no-install-recommends $PHPIZE_DEPS libicu-dev libpq-dev \
    && curl -fsSL --retry 8 --retry-all-errors --retry-delay 5 --max-time 120 \
        -o /tmp/redis.tgz https://pecl.php.net/get/redis-6.3.0.tgz \
    && echo "0d5141f634bd1db6c1ddcda053d25ecf2c4fc1c395430d534fd3f8d51dd7f0b5  /tmp/redis.tgz" | sha256sum -c - \
    && pecl install /tmp/redis.tgz \
    && rm -f /tmp/redis.tgz \
    && docker-php-ext-enable redis \
    && docker-php-ext-install intl pdo_pgsql \
    && apt-get purge --yes --auto-remove $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY --from=dependencies-runtime /app/vendor ./vendor
COPY apps/api .
COPY apps/api/docker/php/conf.d/uploads.ini /usr/local/etc/php/conf.d/uploads.ini
COPY apps/api/docker/php/fpm.d/zz-production-environment.conf /usr/local/etc/php-fpm.d/zz-production-environment.conf

RUN rm --force .env .env.dev .env.test \
    && rm --recursive --force tests \
    && mkdir --parents var/cache var/log \
    && chown --recursive www-data:www-data var

USER www-data

ENTRYPOINT ["php-fpm", "--nodaemonize"]
