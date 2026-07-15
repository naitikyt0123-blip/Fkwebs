FROM php:8.2-apache

# Install required extensions for ZIP extraction and cURL
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install zip curl

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Setup Working Directory
WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html/

# Railway dynamically assigns a PORT, so we configure Apache to listen on it
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html
