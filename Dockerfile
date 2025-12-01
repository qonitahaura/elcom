# Gunakan base image PHP + Apache
FROM php:8.2-apache

# Install extensions yang dibutuhkan Lumen
RUN apt-get update && apt-get install -y \
    unzip \
    libzip-dev \
    && docker-php-ext-install pdo pdo_mysql zip

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy project ke container
COPY . /var/www/html

# Set working directory
WORKDIR /var/www/html

# Install dependency (tanpa dev)
RUN composer install --no-dev --optimize-autoloader

# Set permission folder storage & bootstrap
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port 80
EXPOSE 80

# Jalankan server apache
CMD ["apache2-foreground"]
