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
        libxml2-dev \
        libzip-dev && \  # Added libzip-dev for Zip extension
    pecl install imagick && \
    docker-php-ext-enable imagick && \
    docker-php-ext-install mbstring exif pcntl bcmath gd zip && \  # Added zip here
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Fix ImageMagick PDF policy - Remove or replace restrictive policies
RUN if [ -f /etc/ImageMagick-6/policy.xml ]; then \
        cp /etc/ImageMagick-6/policy.xml /etc/ImageMagick-6/policy.xml.backup && \
        sed -i '/pattern="PDF"/d' /etc/ImageMagick-6/policy.xml; \
    elif [ -f /etc/ImageMagick-7/policy.xml ]; then \
        cp /etc/ImageMagick-7/policy.xml /etc/ImageMagick-7/policy.xml.backup && \
        sed -i '/pattern="PDF"/d' /etc/ImageMagick-7/policy.xml; \
    fi

# Create policy file if it doesn't exist
RUN if [ ! -f /etc/ImageMagick-6/policy.xml ] && [ ! -f /etc/ImageMagick-7/policy.xml ]; then \
        mkdir -p /etc/ImageMagick-6 && \
        echo '<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE policymap [<!ELEMENT policymap (policy)+>]><policymap></policymap>' > /etc/ImageMagick-6/policy.xml; \
    fi

# Enable Apache mod_rewrite and mod_headers
RUN a2enmod rewrite headers

# Enable PHP errors for debugging
RUN echo "display_errors = On" >> /usr/local/etc/php/conf.d/display_errors.ini
RUN echo "log_errors = On" >> /usr/local/etc/php/conf.d/display_errors.ini

# Copy backend files
COPY . /var/www/html/

# Copy custom php.ini
COPY php.ini /usr/local/etc/php/conf.d/

# Ensure permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    chmod 777 /tmp

EXPOSE 80
CMD ["apache2-foreground"]
