FROM php:8.2-cli

RUN apt-get update && apt-get install -y libpq-dev && \
    docker-php-ext-install pdo pdo_pgsql pgsql

WORKDIR /app

COPY . .

CMD php -S 0.0.0.0:$PORT router.php
