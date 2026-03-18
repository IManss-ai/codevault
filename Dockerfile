FROM dunglas/frankenphp:php8.4-bookworm

RUN install-php-extensions pdo_pgsql pgsql

COPY . /app/public

WORKDIR /app/public

ENV SERVER_NAME=":80"
