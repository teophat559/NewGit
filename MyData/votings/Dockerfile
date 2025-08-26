# BVOTE Voting System Dockerfile
# Multi-stage build for production optimization

# Stage 1: Build stage
FROM node:18-alpine AS frontend-build

WORKDIR /app

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm ci --only=production

# Copy source code
COPY . .

# Build frontend
RUN npm run build

# Stage 2: PHP runtime
FROM php:8.1-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    mysql-client \
    redis \
    supervisor \
    curl \
    git \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Copy built frontend assets
COPY --from=frontend-build /app/dist ./public/dist

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Create necessary directories
RUN mkdir -p \
    storage/logs \
    storage/cache \
    storage/sessions \
    storage/uploads \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views

# Set permissions
RUN chown -R www-data:www-data \
    storage \
    bootstrap/cache \
    public/uploads

RUN chmod -R 755 \
    storage \
    bootstrap/cache \
    public/uploads

# Copy configuration files
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh

# Make entrypoint executable
RUN chmod +x /entrypoint.sh

# Expose ports
EXPOSE 80 443

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

# Start services
ENTRYPOINT ["/entrypoint.sh"]
