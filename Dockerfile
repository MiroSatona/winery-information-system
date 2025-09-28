# Dockerfile
FROM php:8.3.26-apache

RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_mysql mysqli

RUN a2enmod rewrite

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ENV APACHE_DOCUMENT_ROOT /var/www/html/www

RUN sed -ri -e 's!/var/www/html!/var/www/html/www!g' /etc/apache2/sites-available/000-default.conf \
    && sed -ri -e 's!/var/www/!/var/www/html/www!g' /etc/apache2/apache2.conf

# Copy composer files first for better caching
WORKDIR /var/www/html
COPY composer.json composer.lock ./

# Install PHP deps at build time
RUN composer install

# Ensure writable dirs exist
RUN mkdir -p /var/www/html/log /var/www/html/temp
RUN chown -R www-data:www-data /var/www/html/log /var/www/html/temp
