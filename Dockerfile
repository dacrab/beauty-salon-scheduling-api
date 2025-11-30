FROM php:8.3-cli-alpine

RUN apk add --no-cache icu-dev oniguruma-dev libzip-dev sqlite-dev git bash \
    && docker-php-ext-install intl pdo pdo_mysql pdo_sqlite opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

COPY . .

RUN composer dump-autoload --optimize \
    && mkdir -p storage/framework/{sessions,views,cache} \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache \
    && touch database/database.sqlite \
    && chmod -R 777 storage bootstrap/cache database \
    && cp .env.example .env

ENV APP_ENV=production
ENV APP_DEBUG=false
ENV DB_CONNECTION=sqlite
ENV DB_DATABASE=/var/www/html/database/database.sqlite
ENV API_TOKEN=demo-token-for-portfolio

RUN php artisan key:generate \
    && php artisan migrate --force \
    && php artisan db:seed --force \
    && php artisan l5-swagger:generate \
    && php artisan view:cache

ENV PORT=8000
EXPOSE 8000

CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=$(($PORT + 0))"]
