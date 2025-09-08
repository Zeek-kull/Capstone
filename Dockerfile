FROM php:8.2-apache
RUN a2enmod rewrite
RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libonig-dev libxml2-dev zip unzip \
 && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd

COPY . /var/www/html/
WORKDIR /var/www/html/
RUN chown -R www-data:www-data /var/www/html
EXPOSE 80
CMD ["apache2-foreground"]