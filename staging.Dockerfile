FROM laravelphp/vapor:php83-arm

COPY ./php.ini /usr/local/etc/php/conf.d/overrides.ini

COPY . /var/task