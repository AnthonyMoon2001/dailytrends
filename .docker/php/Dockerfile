FROM php:8.4-fpm

# Paquetes y extensiones típicas de Symfony
RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libzip-dev libxml2-dev \
 && docker-php-ext-install intl pdo_mysql zip opcache \
 && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
