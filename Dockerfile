# Use the official PHP image with Apache
FROM php:8.2-apache

# Install PostgreSQL system dependencies and PHP extensions
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Enable Apache mod_rewrite (helpful for routing)
RUN a2enmod rewrite

# Copy your project files into the server directory
COPY . /var/www/html/

# Expose port 80 for web traffic
EXPOSE 80
