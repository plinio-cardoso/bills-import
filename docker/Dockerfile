FROM php:8.1-fpm

WORKDIR /var/www/html
RUN apt-get update
RUN apt-get install git -y
RUN apt-get install zip -y

# Extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli
