# ---- Build stage: install deps & composer vendor --------------------------------
FROM php:8.3-fpm AS build

WORKDIR /app

RUN apt-get update && apt-get install -y --no-install-recommends \
    git curl unzip libicu-dev libzip-dev \
    libpng-dev libjpeg-dev libfreetype6-dev \
 && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) intl pdo_mysql zip gd opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# Copy only composer manifests first to cache dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

# Now copy the rest of the source
COPY . .

# ---- Runtime stage: copy only what we need -------------------------------------
FROM php:8.3-fpm AS runtime

# Create non-root user/group (match www-data uid/gid if you like)
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

WORKDIR /var/www/html

# Copy PHP extensions built/installed already are in base image layers
# Copy app & vendor from build stage; own by www-data
COPY --chown=www-data:www-data --from=build /app ./

# Opcache for prod
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
CMD ["php-fpm", "-F"]