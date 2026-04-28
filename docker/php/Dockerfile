FROM php:8.3-fpm-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        curl \
        libssl-dev \
        libicu-dev \
        libzip-dev \
        libsqlite3-dev \
        default-mysql-client \
    && docker-php-ext-install \
        bcmath \
        intl \
        pdo \
        pdo_mysql \
        pdo_sqlite \
        pcntl \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_HOME=/tmp/composer

CMD ["php-fpm"]
