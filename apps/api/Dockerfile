FROM composer:2.10.3 AS composer

FROM php:8.5.9-cli-bookworm AS development

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

COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY docker/php/conf.d/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /app

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
