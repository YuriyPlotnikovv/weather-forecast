# syntax=docker/dockerfile:1

# --- Сборка фронтенда (gulp -> public/) ---
FROM node:22-slim AS build
WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY gulpfile.js ./
COPY src ./src
RUN npm run build

# --- Рантайм: PHP + Apache (htaccess продолжает работать) ---
FROM php:8.3-apache

# mbstring для mb_strtoupper/mb_strtolower, opcache для прод-производительности
RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev \
    && docker-php-ext-install mbstring opcache \
    && rm -rf /var/lib/apt/lists/*

# Модули Apache, которые использует .htaccess (rewrite, expires, deflate, headers)
# access_compat/setenvif/filter обычно включены в Debian по умолчанию — a2enmod для них безопасен
RUN a2enmod rewrite expires deflate headers access_compat setenvif filter \
    && printf '<Directory /var/www/html>\n    AllowOverride All\n    Require all granted\n</Directory>\n' \
        > /etc/apache2/conf-available/allow-htaccess.conf \
    && a2enconf allow-htaccess

# Прод-настройки PHP
RUN { \
        echo 'expose_php = Off'; \
        echo 'display_errors = Off'; \
        echo 'log_errors = On'; \
        echo 'error_log = /dev/stderr'; \
        echo 'date.timezone = UTC'; \
        echo 'opcache.enable = 1'; \
        echo 'opcache.memory_consumption = 128'; \
        echo 'opcache.max_accelerated_files = 10000'; \
        echo 'opcache.validate_timestamps = 0'; \
    } > /usr/local/etc/php/conf.d/prod.ini

COPY --from=build /app/public /var/www/html/public
COPY index.php .htaccess robots.txt favicon.ico favicon.svg apple-touch-icon-180x180.png site.webmanifest /var/www/html/

# Каждая директория отдельной командой: при нескольких директориях-источниках
# COPY склеил бы их содержимое в dest без подпапок
COPY ajax/ /var/www/html/ajax/
COPY core/ /var/www/html/core/
COPY include/ /var/www/html/include/
COPY lang/ /var/www/html/lang/

# Кэш погоды (data/weather.json) и errors.log пишутся в DOCUMENT_ROOT
RUN mkdir -p /var/www/html/data \
    && chown -R www-data:www-data /var/www/html

EXPOSE 80
