# ─── Stage 1: Build Assets ────────────────────────────────────────────────────
FROM node:20-alpine AS node-builder

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build

# ─── Stage 2: Production PHP + Apache ─────────────────────────────────────────
FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions required by Laravel + PostgreSQL
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    opcache

# PHP opcache settings for production performance
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.revalidate_freq=0'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.memory_consumption=192'; \
    echo 'opcache.max_wasted_percentage=10'; \
} > /usr/local/etc/php/conf.d/opcache.ini

# Enable Apache mod_rewrite (required for Laravel routing)
RUN a2enmod rewrite

# ── Apache VirtualHost: point DocumentRoot to Laravel /public ─────────────────
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    \n\
    <Directory /var/www/html/public>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    \n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Install Composer 2
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ── Install PHP dependencies (layer cache: copy manifests first) ───────────────
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-scripts \
    --no-interaction \
    --prefer-dist

# ── Copy full application source ───────────────────────────────────────────────
COPY . .

# ── Copy pre-built frontend assets from node-builder stage ────────────────────
COPY --from=node-builder /app/public/build ./public/build

# ── Run artisan package:discover (needs APP_KEY to bootstrap Laravel) ──────────
# Create a temporary .env ONLY for build time — real secrets come from Render env vars
RUN echo "APP_NAME=RadarIlmu2" > .env \
    && echo "APP_ENV=production" >> .env \
    && echo "APP_KEY=base64:dmFsaWQtYXBwLWtleS1mb3ItZG9ja2VyLWJ1aWxkLW9ubHk=" >> .env \
    && echo "APP_DEBUG=false" >> .env \
    && echo "DB_CONNECTION=pgsql" >> .env \
    && echo "LOG_CHANNEL=stderr" >> .env \
    && composer dump-autoload --optimize \
    && php artisan package:discover --ansi \
    && rm .env

# ── Permissions: www-data must own storage & cache ────────────────────────────
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# ── Copy startup entrypoint ────────────────────────────────────────────────────
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
