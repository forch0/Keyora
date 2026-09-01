FROM php:8.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git curl libpng-dev oniguruma-dev libxml2-dev zip unzip \
    libzip-dev libpq-dev mysql-client redis

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql pdo_pgsql zip exif pcntl gd bcmath

# Install Redis extension (autoconf needed for phpize/pecl)
RUN apk add --no-cache --virtual .build-deps autoconf g++ make \
    && pecl install redis && docker-php-ext-enable redis \
    && apk del .build-deps

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application code from src/ directory
COPY src/ .

# Set permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html/storage
