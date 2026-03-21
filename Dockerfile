FROM dunglas/frankenphp:1-php8.2

RUN install-php-extensions pdo_mysql mysqli

WORKDIR /app

COPY . /app

EXPOSE 8080

ENV SERVER_NAME=:8080

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]