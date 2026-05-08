# Gamitin ang official PHP Apache image
FROM php:8.2-apache

# I-install ang MySQL extensions para sa database (db_config.php)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# I-enable ang Apache mod_rewrite (madalas kailangan sa API)
RUN a2enmod rewrite

# I-copy ang lahat ng files mo papunta sa loob ng Docker container
COPY . /var/www/html/

# I-set ang tamang permissions
RUN chown -R www-data:www-data /var/www/html

# I-expose ang port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]