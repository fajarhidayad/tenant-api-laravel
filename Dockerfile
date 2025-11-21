# Stage 1: Build dependencies
FROM php:8.3-cli AS builder

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite mbstring zip exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Node.js untuk build assets
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

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

# Install dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    sqlite \
    libpng \
    libzip \
    oniguruma \
    && docker-php-ext-install pdo pdo_sqlite mbstring zip exif pcntl bcmath gd

# Set working directory
WORKDIR /var/www/html

# Copy built application from builder
COPY --from=builder /app/vendor ./vendor
COPY --from=builder /app/public ./public
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
    && echo 'php artisan config:cache' >> /entrypoint.sh \
    && echo 'php artisan route:cache' >> /entrypoint.sh \
    && echo 'php artisan view:cache' >> /entrypoint.sh \
    && echo 'php artisan migrate --force' >> /entrypoint.sh \
    && echo 'exec supervisord -c /etc/supervisor/conf.d/supervisord.conf' >> /entrypoint.sh \
    && chmod +x /entrypoint.sh

# Expose port (Dokploy default untuk Laravel: 8000, tapi nginx biasanya 80)
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/up || exit 1

ENTRYPOINT ["/entrypoint.sh"]
