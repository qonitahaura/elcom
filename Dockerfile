FROM php:8.2-apache

# Install extension
RUN apt-get update && apt-get install -y \
    zip unzip git && \
    docker-php-ext-install pdo pdo_mysql

# Enable rewrite
RUN a2enmod rewrite

# Allow .htaccess
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Set DocumentRoot to /public
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Fix ServerName warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# ===============================
# ❤️ Fix wajib untuk Railway
# ===============================
RUN sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf \
    && sed -i 's/:80/:${PORT}/g' /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

# Copy project
COPY . .

# Fix folder permissions
RUN mkdir -p bootstrap/cache storage \
    && chmod -R 775 bootstrap/cache storage \
    && chown -R www-data:www-data bootstrap/cache storage

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --prefer-dist --no-interaction

EXPOSE ${PORT}

CMD ["apache2 -D FOREGROUND"]
