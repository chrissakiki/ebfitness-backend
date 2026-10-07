FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite \
    && echo 'variables_order = "EGPCS"' > /usr/local/etc/php/conf.d/variables.ini

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

EXPOSE 80
