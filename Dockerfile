FROM php:7.1

ENV DEBIAN_FRONTEND noninteractive
ENV COMPOSER_ALLOW_SUPERUSER 1

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
    && rm -rf /var/lib/apt/lists/*

RUN cd /root/ \
  	&& curl -sS https://getcomposer.org/installer | php \
  	&& ln -s /root/composer.phar /usr/local/bin/composer

COPY . /code/
WORKDIR /code/
RUN composer install --no-interaction
CMD ["php", "main.php"]
