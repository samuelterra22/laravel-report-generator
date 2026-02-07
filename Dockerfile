FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libgd-dev \
    libxml2-dev \
    libonig-dev \
    && docker-php-ext-install \
    zip \
    gd \
    xml \
    mbstring \
    bcmath \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
