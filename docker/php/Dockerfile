FROM php:8.3-fpm-alpine

RUN apk add --no-cache icu-dev oniguruma-dev libzip-dev sqlite-dev git bash && \
    docker-php-ext-install intl pdo pdo_mysql pdo_sqlite opcache

WORKDIR /var/www/html

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Ensure storage and bootstrap cache writable
RUN addgroup -g 1000 laravel && adduser -G laravel -g laravel -s /bin/sh -D -u 1000 laravel && \
    chown -R laravel:laravel /var/www/html

COPY --chown=laravel:laravel docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

USER laravel

CMD ["php-fpm"]


