# Stage 1: Build dependencies
FROM php:8.3-cli-alpine AS builder

# Install system dependencies untuk build
RUN apk add --no-cache \
    git \
    unzip \
    curl \
    nodejs \
    npm \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    libzip-dev \
    sqlite-dev \
    && docker-php-ext-install pdo pdo_sqlite mbstring zip exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy composer files first (untuk caching)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy package.json untuk npm install
COPY package.json package-lock.json* ./
RUN npm ci

# Copy semua source code
COPY . .

# Generate autoloader dan optimize
RUN composer dump-autoload --optimize

# Build frontend assets
RUN npm run build

# Stage 2: Production image
FROM php:8.3-fpm-alpine AS production

# Install build dependencies, compile extensions, lalu hapus build deps
RUN apk add --no-cache \
    nginx \
    supervisor \
    sqlite \
    sqlite-libs \
    libpng \
    libzip \
    oniguruma \
    curl \
    # Build dependencies (temporary)
    && apk add --no-cache --virtual .build-deps \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    sqlite-dev \
    # Install PHP extensions
    && docker-php-ext-install pdo pdo_sqlite mbstring zip exif pcntl bcmath gd \
    # Cleanup build dependencies
    && apk del .build-deps \
    && rm -rf /var/cache/apk/*

# Create necessary directories
RUN mkdir -p /var/log/supervisor \
    && mkdir -p /run/nginx

# Set working directory
WORKDIR /var/www/html

# Copy built application from builder
COPY --from=builder /app .

# Create SQLite database directory
RUN mkdir -p /var/www/html/database \
    && touch /var/www/html/database/database.sqlite

# Copy configuration files
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/database

# Create entrypoint script
RUN echo '#!/bin/sh' > /entrypoint.sh \
    && echo 'set -e' >> /entrypoint.sh \
    && echo 'php artisan config:cache' >> /entrypoint.sh \
    && echo 'php artisan route:cache' >> /entrypoint.sh \
    && echo 'php artisan view:cache' >> /entrypoint.sh \
    && echo 'php artisan migrate --force' >> /entrypoint.sh \
    && echo 'exec supervisord -c /etc/supervisor/conf.d/supervisord.conf' >> /entrypoint.sh \
    && chmod +x /entrypoint.sh

# Expose port
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=10s --retries=3 \
    CMD curl -f http://localhost/up || exit 1

ENTRYPOINT ["/entrypoint.sh"]
