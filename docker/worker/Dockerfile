FROM php:7.4-cli-alpine

RUN apk add --no-cache bash git unzip $PHPIZE_DEPS curl-dev openssl-dev \
  && docker-php-ext-install pdo pdo_mysql sockets curl \
  && pecl install redis \
  && docker-php-ext-enable redis \
  && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/backend
CMD ["php", "-v"]
