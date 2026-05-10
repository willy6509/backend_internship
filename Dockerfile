# Multi-stage build for production-ready Laravel container
FROM php:8.3-fpm-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    libpq-dev \
    postgresql-client \
    git \
    zip \
    unzip \
    curl \
    bash \
    supervisor \
    nginx

# Install PHP extensions
RUN docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    bcmath \
    ctype \
    fileinfo \
    json \
    mbstring \
    tokenizer \
    xml

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy application files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set permissions
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache

# Create necessary directories
RUN mkdir -p /app/storage/logs \
    && mkdir -p /app/storage/app/public \
    && mkdir -p /app/bootstrap/cache \
    && chown -R www-data:www-data /app/storage /app/bootstrap

# Create symlink for public storage
RUN php artisan storage:link --force || true

# Copy nginx configuration
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisor.conf /etc/supervisor/conf.d/supervisor.conf

# Generate application key if not present
RUN if [ ! -f .env ]; then cp .env.example .env && php artisan key:generate; fi

# Run database migrations and seeders (if needed)
RUN php artisan migrate --force || true

# Expose ports
EXPOSE 80 443

# Start supervisor (manages PHP-FPM and Nginx)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisor.conf"]

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/up || exit 1
