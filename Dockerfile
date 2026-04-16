ARG PHP_VERSION=8.4
FROM php:$PHP_VERSION-fpm-alpine

ARG CONTAINER_USER=app
ARG CONTAINER_UID=1000

# Install dependencies, PHP extensions, create user/group, and directories in one layer
RUN apk add --no-cache git curl zip unzip sqlite-dev \
    && docker-php-ext-install pdo_mysql pdo_sqlite bcmath \
    && if ! getent group www-data; then addgroup -g 82 www-data; fi \
    && adduser -u $CONTAINER_UID -D -G www-data -h /home/$CONTAINER_USER $CONTAINER_USER \
    && mkdir -p /home/$CONTAINER_USER/.composer \
             /home/$CONTAINER_USER/php-fpm/logs \
    && chown -R $CONTAINER_USER:www-data /home/$CONTAINER_USER

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Dependency layer (cache-friendly)
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-ansi --no-progress

# Application source
COPY . .
RUN chown -R "${CONTAINER_USER}":www-data /var/www

USER $CONTAINER_USER

CMD ["php-fpm"]
