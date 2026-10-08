# Build:  docker build -t beauty-salon-api .
# Run:    docker run -p 8080:8080 -e API_TOKEN=secret beauty-salon-api
FROM php:8.3-fpm-alpine

# nginx + php-fpm run as this unprivileged user (see docker/ for their configs).
ARG APP_USER=app
ARG APP_UID=1000

RUN apk add --no-cache nginx icu-dev oniguruma-dev libzip-dev sqlite-dev git bash \
    && docker-php-ext-install intl pdo pdo_mysql pdo_sqlite opcache \
    && adduser -D -u ${APP_UID} -s /bin/sh ${APP_USER}

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Dependencies are installed before the app code so the layer is cached.
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

COPY . .
COPY --chown=${APP_USER} docker/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY --chown=${APP_USER} docker/nginx.conf /etc/nginx/nginx.conf.template
COPY --chown=${APP_USER} docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY --chown=${APP_USER} docker/entrypoint.sh /usr/local/bin/entrypoint.sh

# The image's stock www pool listens on a socket; replace it with the app's own.
RUN rm -f /usr/local/etc/php-fpm.d/www.conf \
    && chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p storage/framework/{cache/data,sessions,views} storage/logs storage/api-docs bootstrap/cache \
    && touch database/database.sqlite \
    && chown -R ${APP_USER} .env storage bootstrap/cache database

USER ${APP_USER}

ENV APP_ENV=production \
    APP_DEBUG=false \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/var/www/html/database/database.sqlite \
    API_TOKEN=demo-token-for-portfolio \
    PORT=8080

RUN php artisan key:generate \
    && php artisan migrate --force \
    && php artisan db:seed --force \
    && php artisan l5-swagger:generate \
    && php artisan view:cache

EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
