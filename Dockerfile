FROM php:8.2-cli

# Extensions PHP nécessaires à Symfony et Doctrine
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev libicu-dev \
    && docker-php-ext-install pdo_mysql intl zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

ENV APP_ENV=dev \
    COMPOSER_ALLOW_SUPERUSER=1

RUN composer install --no-interaction --prefer-dist --no-progress \
    && chmod +x docker/entrypoint.sh

EXPOSE 8000
ENTRYPOINT ["docker/entrypoint.sh"]
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
