FROM php:8.3-fpm-alpine AS base

RUN apk add --no-cache \
    libpq-dev \
    libxml2-dev \
    oniguruma-dev \
    icu-dev \
    postgresql-client \
    git \
    zip \
    unzip \
    curl \
    bash \
    supervisor \
    nginx \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    bcmath \
    fileinfo \
    mbstring \
    xml \
    intl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN mkdir -p /app/storage/logs \
    && mkdir -p /app/storage/app/public \
    && mkdir -p /app/bootstrap/cache \
    && mkdir -p /var/log/supervisor \
    && mkdir -p /var/run \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache

RUN php artisan storage:link --force || true

COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisor.conf /etc/supervisor/conf.d/supervisor.conf

EXPOSE 80 443

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisor.conf"]

HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/up || exit 1
