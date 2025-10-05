# PHP + Apache
FROM php:8.2-apache

# Install dependencies for Imagick
RUN apt-get update && \
    apt-get install -y imagemagick ghostscript libmagickwand-dev zip unzip && \
    pecl install imagick && \
    docker-php-ext-enable imagick

# Copy backend files
COPY . /var/www/html/

# Copy custom php.ini
COPY php.ini /usr/local/etc/php/

# Ensure permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
CMD ["apache2-foreground"]
