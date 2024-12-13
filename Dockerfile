FROM dunglas/frankenphp:1.2-builder-php8.3.7
RUN install-php-extensions \
    pdo_mysql \
    gd \
    intl \
    zip \
    opcache \
    pcntl \
    bcmath
    
#COPY . /app

WORKDIR /app