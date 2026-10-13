FROM php:8.4-fpm

# Instalador de extensiones PHP (mlocati/php-extension-installer, MIT)
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql redis bcmath intl zip gd pcntl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
