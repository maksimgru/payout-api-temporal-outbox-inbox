FROM ghcr.io/roadrunner-server/roadrunner:latest AS roadrunner
FROM php:8.4-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        procps \
        util-linux \
        libzip-dev \
        libonig-dev \
        libcurl4-openssl-dev \
        libssl-dev \
        zlib1g-dev \
        default-mysql-client \
        $PHPIZE_DEPS \
    && docker-php-ext-install pdo_mysql mbstring bcmath pcntl zip curl sockets \
    && pecl install grpc protobuf \
    && docker-php-ext-enable grpc protobuf \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=roadrunner /usr/bin/rr /usr/local/bin/rr

WORKDIR /var/www/html
COPY . /var/www/html

RUN composer install --prefer-dist --no-interaction --no-progress --optimize-autoloader \
    && chmod +x /var/www/html/.docker/entrypoint.sh /var/www/html/artisan \
    && mkdir -p storage/framework/{cache/data,cache/doctrine/proxies,sessions,testing,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8000

ENTRYPOINT ["/var/www/html/.docker/entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
