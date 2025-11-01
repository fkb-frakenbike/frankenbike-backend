# ---- Build stage
FROM php:8.3-fpm AS build
WORKDIR /app

RUN apt-get update && apt-get install -y --no-install-recommends \
    git curl unzip libicu-dev libzip-dev \
    libpng-dev libjpeg-dev libfreetype6-dev \
 && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-jpeg --with-freetype \
 && docker-php-ext-install -j$(nproc) intl pdo_mysql zip gd opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# Cache Composer layers
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader --no-scripts

# Copy full app
COPY . .

RUN composer dump-autoload --no-dev --classmap-authoritative

# ---- Runtime stage
FROM php:8.3-fpm AS runtime
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data
WORKDIR /var/www/html

COPY --chown=www-data:www-data --from=build /app ./

# same entrypoint you already have
COPY docker/php/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Opcache in prod
RUN { \
  echo 'opcache.enable=1'; \
  echo 'opcache.enable_cli=0'; \
  echo 'opcache.validate_timestamps=0'; \
  echo 'opcache.max_accelerated_files=20000'; \
  echo 'opcache.memory_consumption=128'; \
  echo 'opcache.interned_strings_buffer=16'; \
} > /usr/local/etc/php/conf.d/opcache.ini

USER www-data
EXPOSE 9000
ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm", "-F"]