FROM php:7.3-apache
RUN apt-get update && apt-get install -y libmagickwand-dev librsvg2-bin --no-install-recommends && rm -rf /var/lib/apt/lists/*
RUN printf "\n" | pecl install imagick
RUN mkdir -p /tmp
run chmod a+rw /tmp

RUN docker-php-ext-enable imagick
