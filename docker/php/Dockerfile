FROM php:8.3-fpm-alpine

# System deps
RUN apk add --no-cache \
    bash git curl zip unzip icu-dev libzip-dev oniguruma-dev \
    mysql-client linux-headers $PHPIZE_DEPS \
    && docker-php-ext-install pdo pdo_mysql mbstring zip intl opcache pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS linux-headers \
    && rm -rf /tmp/* /var/cache/apk/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# PHP config
COPY docker/php/php.ini /usr/local/etc/php/conf.d/stockly.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Ensure www-data owns storage/bootstrap cache at runtime (entrypoint handles perms)
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
