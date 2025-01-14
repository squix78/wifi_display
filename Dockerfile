
FROM php:7.4-apache
RUN echo "deb http://deb.debian.org/debian bullseye main contrib non-free" > /etc/apt/sources.list && \
    echo "deb http://deb.debian.org/debian bullseye-updates main contrib non-free" >> /etc/apt/sources.list && \
    echo "deb http://deb.debian.org/debian-security/ bullseye-security main contrib non-free" >> /etc/apt/sources.list && \
    apt-get update
RUN apt-get install -y --no-install-recommends \
    imagemagick \
    ghostscript \
    libmagickwand-dev \
    librsvg2-bin \
    xfonts-100dpi \
    xfonts-75dpi \
    xfonts-base \
    fonts-roboto \
    fonts-inconsolata \
    ttf-mscorefonts-installer \
    fonts-open-sans \
    fontconfig 
COPY server/fontconfig/* /etc/fonts/conf.d/
COPY php.ini /usr/local/etc/php/
RUN fc-cache -f -v
RUN printf "\n" | pecl install imagick
RUN mkdir -p /tmp
RUN chmod a+rw /tmp
RUN ln -s -f /usr/share/zoneinfo/Europe/Zurich /etc/localtime
RUN docker-php-ext-enable imagick

