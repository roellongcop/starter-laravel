FROM php:8.4-fpm

# --- System dependencies ---
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        zip \
        curl \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libonig-dev \
        libxml2-dev \
        default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# --- PHP extensions (via mlocati helper for reliability) ---
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions \
    && install-php-extensions \
        pdo_mysql \
        gd \
        zip \
        exif \
        bcmath \
        intl \
        pcntl \
        opcache

# --- Composer ---
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# --- Remap www-data to the host UID/GID so mounted files stay writable ---
ARG UID=1000
ARG GID=1000
RUN usermod -u ${UID} www-data \
    && groupmod -g ${GID} www-data \
    && chown -R www-data:www-data /var/www

# --- PHP runtime config ---
COPY docker/app/php.ini /usr/local/etc/php/conf.d/zz-app.ini

WORKDIR /var/www/html

USER www-data

EXPOSE 9000
CMD ["php-fpm"]
