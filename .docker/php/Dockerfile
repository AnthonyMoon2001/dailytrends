FROM php:8.4-fpm-alpine

# Dependencias de sistema
RUN apk add --no-cache \
    git curl unzip bash icu-dev libzip-dev oniguruma-dev libpng-dev libjpeg-turbo-dev libwebp-dev freetype-dev

# Extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install -j$(nproc) \
    pdo pdo_mysql intl mbstring opcache zip gd

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash \
 && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

# Crear usuario no root con tu UID/UNAME para mapear permisos
ARG UID=1000
ARG UNAME=dev
RUN adduser -D -u ${UID} ${UNAME}
USER ${UNAME}

WORKDIR /var/www/html
