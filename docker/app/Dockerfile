ARG PHP_VERSION=8.3
FROM php:${PHP_VERSION}-fpm

ARG UID
ARG GID

# Packages
RUN apt-get update \
    && apt-get install -y \
        git \
        zip \
        unzip \
        libzip-dev \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        supervisor \
    && apt-get clean

# GD (libfreetype6-dev, libjpeg62-turbo-dev, libpng-dev)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd

# Simple-excel
RUN docker-php-ext-install zip

RUN docker-php-ext-configure pcntl --enable-pcntl \
  && docker-php-ext-install pcntl

# Redis
RUN pecl install redis-6.1.0 \
    && docker-php-ext-enable redis

# PostgreSQL
RUN apt-get install -y libpq-dev \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo_pgsql

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/bin --filename=composer --quiet

# Non root user
RUN groupadd -g $GID rbz \
    && useradd -u $UID -ms /bin/bash -g rbz rbz

WORKDIR /var/www