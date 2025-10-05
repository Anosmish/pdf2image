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
        libzip-dev && \
    pecl install imagick && \
    docker-php-ext-enable imagick && \
    docker-php-ext-install mbstring exif pcntl bcmath gd zip && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Configure ImageMagick policy to allow PDF
RUN if [ -f /etc/ImageMagick-6/policy.xml ]; then \
        sed -i 's/rights="none" pattern="PDF"/rights="read | write" pattern="PDF"/' /etc/ImageMagick-6/policy.xml; \
    elif [ -f /etc/ImageMagick-7/policy.xml ]; then \
        sed -i 's/rights="none" pattern="PDF"/rights="read | write" pattern="PDF"/' /etc/ImageMagick-7/policy.xml; \
    elif [ -f /etc/ImageMagick/policy.xml ]; then \
        sed -i 's/rights="none" pattern="PDF"/rights="read | write" pattern="PDF"/' /etc/ImageMagick/policy.xml; \
    else \
        echo "No policy.xml found, creating one..." && \
        mkdir -p /etc/ImageMagick-6 && \
        echo '<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE policymap [<!ELEMENT policymap (policy)+>]><policymap><policy domain="coder" rights="read | write" pattern="PDF" /></policymap>' > /etc/ImageMagick-6/policy.xml; \
    fi

# Alternative: Completely replace the policy file to allow everything
RUN if [ -f /etc/ImageMagick-6/policy.xml ]; then \
        cp /etc/ImageMagick-6/policy.xml /etc/ImageMagick-6/policy.xml.bak && \
        echo '<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE policymap [<!ELEMENT policymap (policy)+>]><policymap></policymap>' > /etc/ImageMagick-6/policy.xml; \
    elif [ -f /etc/ImageMagick-7/policy.xml ]; then \
        cp /etc/ImageMagick-7/policy.xml /etc/ImageMagick-7/policy.xml.bak && \
        echo '<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE policymap [<!ELEMENT policymap (policy)+>]><policymap></policymap>' > /etc/ImageMagick-7/policy.xml; \
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
