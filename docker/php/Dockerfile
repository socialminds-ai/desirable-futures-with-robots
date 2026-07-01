FROM php:8.3-apache

# PDO MySQL driver (not bundled in the base image) + URL rewriting.
RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite

# Serve from public/ so lib/, db/, config/ and secrets stay outside the docroot.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
