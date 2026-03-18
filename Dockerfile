FROM dunglas/frankenphp:php8.4-bookworm

RUN install-php-extensions pdo_pgsql pgsql

WORKDIR /app

COPY . .

ENV FRANKENPHP_CONFIG="worker ./index.php"
ENV SERVER_NAME=":${PORT:-80}"
