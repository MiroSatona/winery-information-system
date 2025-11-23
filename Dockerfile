# Dockerfile
FROM php:8.3.26-apache

RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_mysql mysqli

RUN apt-get update && apt-get install -y libjpeg-dev libpng-dev libwebp-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install gd

RUN apt-get update && apt-get install -y libicu-dev \
    && docker-php-ext-install intl

RUN a2enmod rewrite

# Allow .htaccess overrides in DocumentRoot
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ENV APACHE_DOCUMENT_ROOT /var/www/html/www

RUN sed -ri -e 's!/var/www/html!/var/www/html/www!g' /etc/apache2/sites-available/000-default.conf \
    && sed -ri -e 's!/var/www/!/var/www/html/www!g' /etc/apache2/apache2.conf

# Copy composer files
WORKDIR /var/www/html
COPY composer.json ./

# Install PHP deps
RUN composer install

# Ensure writable dirs exist
RUN mkdir -p /var/www/html/log /var/www/html/temp
RUN chown -R www-data:www-data /var/www/html/log /var/www/html/temp
