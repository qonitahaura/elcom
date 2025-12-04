FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    zip unzip git && \
    docker-php-ext-install pdo pdo_mysql

RUN a2enmod rewrite

WORKDIR /var/www/html

COPY . .

RUN mkdir -p bootstrap/cache storage \
    && chmod -R 775 bootstrap/cache storage \
    && chown -R www-data:www-data bootstrap/cache storage

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --prefer-dist --no-interaction

# ===== IMPORTANT FIX FOR RAILWAY =====
# Use Railway's dynamic PORT
ENV PORT=8080

# Make Apache use the dynamic port
RUN echo "Listen ${PORT}" > /etc/apache2/ports.conf
RUN sed -i "s|<VirtualHost \*:80>|<VirtualHost *:${PORT}>|" /etc/apache2/sites-available/000-default.conf

# Force Apache root to /public
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

EXPOSE 8080

CMD ["apache2-foreground"]
