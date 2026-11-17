FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libpq-dev \
    && docker-php-ext-install intl pdo_pgsql opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV APP_SECRET=build-only-secret

COPY . .

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --optimize-autoloader

RUN php bin/console asset-map:compile

RUN printf '%s\n' \
    'Listen 10000' \
    > /etc/apache2/ports.conf

RUN printf '%s\n' \
    '<VirtualHost *:10000>' \
    '    ServerName localhost' \
    '    DocumentRoot /var/www/html/public' \
    '    DirectoryIndex index.php' \
    '    <Directory /var/www/html/public>' \
    '        AllowOverride None' \
    '        Require all granted' \
    '        FallbackResource /index.php' \
    '    </Directory>' \
    '    ErrorLog ${APACHE_LOG_DIR}/error.log' \
    '    CustomLog ${APACHE_LOG_DIR}/access.log combined' \
    '</VirtualHost>' \
    > /etc/apache2/sites-available/000-default.conf

RUN mkdir -p public/uploads/articles \
    && chown -R www-data:www-data var public/uploads

EXPOSE 10000

CMD ["apache2-foreground"]
