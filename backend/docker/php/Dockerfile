FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    git \
    curl \
    libzip-dev \
    libpng-dev \
    oniguruma-dev \
    icu-dev \
    $PHPIZE_DEPS \
    && docker-php-ext-install pdo_mysql zip gd mbstring intl bcmath \
    && pecl install redis && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize

CMD ["php-fpm"]
