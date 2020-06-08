FROM php:7.4-fpm
RUN apt-get update && apt-get install -y

# PHP Configuration
COPY conf.d/* /usr/local/etc/php/conf.d/

# NodeJS + yarn installation
RUN curl https://deb.nodesource.com/setup_12.x | bash
RUN curl https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add -
RUN echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list
RUN apt-get update && apt-get install -y nodejs yarn

RUN apt-get clean && rm -rf /var/lib/apt/lists/*

RUN apt-get update && apt-get install -y --no-install-recommends \
	wget \
	zlib1g-dev \
	libxml2-dev \
	libzip-dev \
 && docker-php-ext-install \
	zip \
	intl \
	mysqli \
	opcache \
	pdo pdo_mysql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan Commands
RUN useradd -G www-data,root -u 1000 -d /home/myhordes myhordes
RUN mkdir -p /home/myhordes/.composer && \
    chown -R myhordes:myhordes /home/myhordes

# Set working directory
WORKDIR /var/www/html

EXPOSE 9000