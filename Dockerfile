FROM dunglas/frankenphp:php8.3-bookworm

WORKDIR /app

RUN install-php-extensions \
    pdo_pgsql \
    mbstring \
    bcmath \
    intl \
    zip \
    opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

COPY package.json package-lock.json ./

RUN apt-get update \
    && apt-get install -y nodejs npm \
    && npm install \
    && npm run build \
    && rm -rf /var/lib/apt/lists/*

COPY . .

RUN mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    && chmod -R 775 storage bootstrap/cache

RUN php artisan optimize:clear

EXPOSE 10000

CMD ["frankenphp", "php-server", "--listen", ":10000", "--root", "/app/public"]
