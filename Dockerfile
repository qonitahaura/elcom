FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    zip unzip git && \
    docker-php-ext-install pdo pdo_mysql

RUN a2enmod rewrite

# Set DocumentRoot ke /public
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Tambah ServerName biar tidak error FQDN
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

WORKDIR /var/www/html

COPY . .

RUN mkdir -p bootstrap/cache storage \
    && chmod -R 775 bootstrap/cache storage \
    && chown -R www-data:www-data bootstrap/cache storage

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --prefer-dist --no-interaction

EXPOSE 80

CMD ["apache2-foreground"]
