# Stage 1: Build stage
FROM php:8.2-cli-alpine AS builder

RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    nodejs \
    npm

RUN docker-php-ext-install pdo pdo_sqlite zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

COPY package.json package-lock.json* ./
RUN npm ci --only=production

COPY . .
RUN npm run build

# Stage 2: Production
FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    nginx \
    supervisor \
    libpng \
    libzip \
    sqlite

RUN docker-php-ext-install pdo pdo_sqlite zip

RUN addgroup -g 1000 www && \
    adduser -u 1000 -G www -s /bin/sh -D www

WORKDIR /var/www/html

COPY --from=builder --chown=www:www /var/www/html /var/www/html

# Nginx config
RUN echo 'server { listen 80; server_name _; root /var/www/html/public; index index.php; charset utf-8; location / { try_files $uri $uri/ /index.php?$query_string; } location ~ \.php$ { fastcgi_pass 127.0.0.1:9000; fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name; include fastcgi_params; } }' > /etc/nginx/http.d/default.conf

# Supervisor config
RUN echo '[supervisord]\nnodaemon=true\n\n[program:php-fpm]\ncommand=php-fpm\nautostart=true\nautorestart=true\n\n[program:nginx]\ncommand=nginx -g "daemon off;"\nautostart=true\nautorestart=true' > /etc/supervisord.conf

RUN mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache database && \
    chown -R www:www storage bootstrap/cache database && \
    chmod -R 775 storage bootstrap/cache database

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
