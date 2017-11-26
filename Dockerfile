FROM php:7.1

ENV DEBIAN_FRONTEND noninteractive
ENV COMPOSER_ALLOW_SUPERUSER 1

# Install dependencies
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        openssh-server \
        unzip \
    && rm -rf /var/lib/apt/lists/*

# Install composer, configure git, and authorize SSH github
RUN cd /root/ \
  	&& curl -sS https://getcomposer.org/installer | php \
  	&& ln -s /root/composer.phar /usr/local/bin/composer \
	&& git config --global user.email "ondrej.popelka@keboola.com" \
	&& git config --global user.name "Robot Robotic" \
	&& git config --global push.default simple \
	&& mkdir -p /root/.ssh \
    && chmod 0700 /root/.ssh \
    && ssh-keyscan github.com > /root/.ssh/known_hosts

COPY ./config /root/.ssh/config


COPY . /code/
WORKDIR /code/
RUN composer install --no-interaction
CMD ["php", "main.php"]
