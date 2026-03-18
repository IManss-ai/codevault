FROM php:8.2-apache

RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && a2enmod rewrite

COPY 000-default.conf /etc/apache2/sites-available/000-default.conf
COPY . /var/www/html/
