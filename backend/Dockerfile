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
        libpng-dev \
        libonig-dev \
        libxml2-dev && \
    pecl install imagick && \
    docker-php-ext-enable imagick && \
    docker-php-ext-install mbstring exif pcntl bcmath gd

# Configure ImageMagick policy to allow PDF
RUN sed -i 's/rights="none" pattern="PDF"/rights="read | write" pattern="PDF"/' /etc/ImageMagick-6/policy.xml

# Enable Apache mod_rewrite and mod_headers
RUN a2enmod rewrite headers

# Enable PHP errors for debugging
RUN echo "display_errors = On" >> /usr/local/etc/php/conf.d/display_errors.ini
RUN echo "log_errors = On" >> /usr/local/etc/php/conf.d/display_errors.ini

# Copy backend files
COPY . /var/www/html/

# Copy custom php.ini (if you have any)
COPY php.ini /usr/local/etc/php/conf.d/

# Ensure permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    chmod 777 /tmp

EXPOSE 80
CMD ["apache2-foreground"]
