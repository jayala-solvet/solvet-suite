# syntax=docker/dockerfile:1
FROM php:8.5-fpm-bookworm AS app
RUN apt-get update && apt-get install -y --no-install-recommends libicu-dev libpq-dev libzip-dev libpng-dev libonig-dev libsqlite3-dev unzip \
 && docker-php-ext-install -j"$(nproc)" intl pdo_pgsql pdo_sqlite bcmath zip gd mbstring \
 && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2.10 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY . .
ARG INSTALL_DEV=false
RUN if [ "$INSTALL_DEV" = "true" ]; then composer install --prefer-dist --no-interaction --no-progress --no-scripts; else composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts; fi \
 && php artisan package:discover \
 && php artisan filament:upgrade \
 && mkdir -p storage/app/private storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache
COPY docker/php.ini /usr/local/etc/php/conf.d/solvet.ini
USER www-data
EXPOSE 9000
CMD ["php-fpm"]

FROM nginx:1.28-alpine AS web
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public
EXPOSE 8080
