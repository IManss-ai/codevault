FROM php:8.2-apache

RUN apt-get update && apt-get install -y libpq-dev && \
    docker-php-ext-install pdo pdo_pgsql pgsql && \
    a2enmod rewrite

COPY . /var/www/html/

RUN echo '<Directory /var/www/html>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/codevault.conf && \
    a2enconf codevault

ENV APACHE_LOG_DIR=/var/log/apache2

EXPOSE 80
