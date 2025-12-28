# Multi-stage Dockerfile for Laravel Application

# Stage 1: Base PHP image with extensions
FROM php:8.4-fpm-alpine AS base

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    bash \
    curl \
    git \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    libzip-dev \
    oniguruma-dev \
    mysql-client \
    postgresql-dev \
    icu-dev \
    linux-headers \
    && docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache

# Install Redis extension
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Configure PHP for production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php/custom.ini "$PHP_INI_DIR/conf.d/custom.ini"

# Stage 2: Composer dependencies
FROM base AS composer

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock ./

# Install composer dependencies (production only, optimized)
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# Stage 3: Node build for frontend assets
FROM node:20-alpine AS node

WORKDIR /var/www/html

# Copy package files
COPY package*.json ./
COPY vite.config.js ./

# Install npm dependencies
RUN npm ci

# Copy application source for Vite build
COPY resources ./resources
COPY public ./public

# Build frontend assets
RUN npm run build

# Stage 4: Final production image
FROM base

WORKDIR /var/www/html

# Copy composer binary for runtime dependency installation
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . .

# Copy vendor from composer stage
COPY --from=composer /var/www/html/vendor ./vendor

# Copy built assets from node stage
COPY --from=node /var/www/html/public/build ./public/build

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Copy entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
