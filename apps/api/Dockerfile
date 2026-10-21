FROM composer:2.10.2 AS composer

FROM php:8.5.12-cli-bookworm AS development

RUN apt-get update \
    && apt-get install --yes --no-install-recommends $PHPIZE_DEPS libicu-dev libpq-dev unzip \
    && curl -fsSL --retry 8 --retry-all-errors --retry-delay 5 --max-time 120 \
        -o /tmp/phpredis.tar.gz https://github.com/phpredis/phpredis/archive/refs/tags/6.3.0.tar.gz \
    && echo "cb8f81df1a275599e4f8ddcfec7e1f65ed1953e6f5673649149fd680ebff4cad  /tmp/phpredis.tar.gz" | sha256sum -c - \
    && docker-php-source extract \
    && mkdir -p /usr/src/php/ext/redis \
    && tar -xzf /tmp/phpredis.tar.gz --strip-components=1 -C /usr/src/php/ext/redis \
    && rm -f /tmp/phpredis.tar.gz \
    && docker-php-ext-install -j "$(nproc)" redis \
    && docker-php-ext-install intl pdo_pgsql \
    && docker-php-source delete \
    && apt-get purge --yes --auto-remove $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY docker/php/conf.d/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /app

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
