# PHP + Apache
FROM php:8.2-apache

# Install dependencies
RUN apt-get update && \
    apt-get install -y \
        imagemagick \
        ghostscript \
        libmagickwand-dev \
        zip \
        unzip \
        fonts-dejavu-core \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev && \
    pecl install imagick && \
    docker-php-ext-enable imagick

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Enable PHP errors for debugging
RUN echo "display_errors = On" > /usr/local/etc/php/conf.d/display_errors.ini

# Copy backend files
COPY . /var/www/html/

# Copy custom php.ini (if you have any)
COPY php.ini /usr/local/etc/php/

# Ensure permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod 777 /tmp

EXPOSE 80
CMD ["apache2-foreground"]
