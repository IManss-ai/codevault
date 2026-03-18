FROM php:8.2-apache

RUN apt-get update && apt-get install -y libpq-dev && \
    docker-php-ext-install pdo pdo_pgsql pgsql && \
    a2enmod rewrite && \
    a2dismod mpm_event && \
    a2enmod mpm_prefork

COPY . /var/www/html/

COPY apache.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 80
