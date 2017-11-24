FROM php:7.1

ENV DEBIAN_FRONTEND noninteractive
ENV COMPOSER_ALLOW_SUPERUSER 1

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        expect \
        openssh-server \
        unzip \
    && rm -rf /var/lib/apt/lists/*

RUN cd /root/ \
  	&& curl -sS https://getcomposer.org/installer | php \
  	&& ln -s /root/composer.phar /usr/local/bin/composer

RUN git config --global user.email "ondrej.popelka@keboola.com" \
	&& git config --global user.name "Robot Robotic" \
	&& git config --global push.default simple

COPY ./config /root/.ssh/config

# Authorize SSH Host
RUN mkdir -p /root/.ssh && \
    chmod 0700 /root/.ssh && \
    ssh-keyscan github.com > /root/.ssh/known_hosts

COPY ./key.pub /root/.ssh/key.pub

COPY . /code/
WORKDIR /code/
RUN composer install --no-interaction
CMD ["php", "main.php"]
