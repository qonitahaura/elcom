# Use official PHP image with required extensions
FROM php:8.2-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    zip unzip git && \
    docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Set permissions
RUN mkdir -p bootstrap/cache storage \
    && chmod -R 775 bootstrap/cache storage \
    && chown -R www-data:www-data bootstrap/cache storage

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install PHP dependencies
RUN composer install --no-dev --prefer-dist --no-interaction

# Set public/ as Apache root
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Force Apache to run on Railway's PORT (3000)
RUN sed -i "s/Listen 80/Listen 3000/" /etc/apache2/ports.conf
RUN sed -i "s/:80>/:3000>/" /etc/apache2/sites-available/000-default.conf

EXPOSE 3000

CMD ["apache2-foreground"]
