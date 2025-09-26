# docker/php/Dockerfile
FROM php:8.3-fpm

# 1) System deps required by PHP extensions you already use
RUN apt-get update && apt-get install -y --no-install-recommends \
    git curl unzip \
    libpng-dev libjpeg-dev libfreetype6-dev \
 && rm -rf /var/lib/apt/lists/*

# 2) Configure + build gd (jpeg + freetype) and pdo_mysql
#    (Avoids common "gd configure" failures)
RUN docker-php-ext-configure gd --with-jpeg --with-freetype \
 && docker-php-ext-install -j$(nproc) gd pdo_mysql

# 3) Increase PHP upload limits for local dev (so multipart images work)
#    (Create the conf.d dir explicitly to avoid "No such file or directory")
RUN mkdir -p /usr/local/etc/php/conf.d \
 && { \
      echo 'upload_max_filesize=20M'; \
      echo 'post_max_size=20M'; \
      echo 'memory_limit=512M'; \
    } > /usr/local/etc/php/conf.d/uploads.ini

# 4) Install Composer (same as you had, but earlier in the layer order)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 5) App files
WORKDIR /var/www/html
COPY . ./

# 6) Reasonable ownership (optional in dev)
RUN chown -R www-data:www-data /var/www/html

# 7) fpm port (compose overrides CMD to run php -S; this is fine)
EXPOSE 9000
CMD ["php-fpm"]

