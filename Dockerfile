FROM php:8.2-apache

# Install extension
RUN apt-get update && apt-get install -y \
    zip unzip git && \
    docker-php-ext-install pdo pdo_mysql

RUN a2enmod rewrite

# Enable .htaccess
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Set DocumentRoot to /public
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

WORKDIR /var/www/html

COPY . .

RUN mkdir -p bootstrap/cache storage \
    && chmod -R 775 bootstrap/cache storage \
    && chown -R www-data:www-data bootstrap/cache storage

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --prefer-dist --no-interaction

# =======================================================
# ❤️ Runtime port binding for Railway (PENTING)
# =======================================================
COPY set-port.sh /set-port.sh
RUN chmod +x /set-port.sh

EXPOSE 8080

CMD ["/set-port.sh"]
