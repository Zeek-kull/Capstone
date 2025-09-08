FROM php:8.2-apache

# Enable rewrite and install extensions commonly needed
RUN a2enmod rewrite

RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libonig-dev libxml2-dev zip unzip git \
 && docker-php-ext-install pdo pdo_mysql mbstring exif bcmath gd

# Copy app
COPY . /var/www/html/
WORKDIR /var/www/html/

# Ensure uploads directory writable (adjust path if different)
RUN mkdir -p /var/www/html/admin/uploaded_products \
 && chown -R www-data:www-data /var/www/html

EXPOSE 80
CMD ["apache2-foreground"]