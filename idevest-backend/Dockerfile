FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    postgresql-dev \
    libpng-dev \
    libzip-dev \
    zip \
    && docker-php-ext-install pdo pdo_pgsql gd zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader \
    && php artisan key:generate --force \
    && php artisan storage:link \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8000
CMD ["sh", "-c", "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000"]