#!/usr/bin/env bash

docker-compose down -v

chmod +x docker-start.sh

docker  run --rm -v $(pwd):/app -w /app composer install \
        -vvv \
        --ignore-platform-reqs \
        --no-interaction \
        --no-plugins \
        --no-scripts \
        --prefer-dist


docker run --rm -v $(pwd):/app -w /app node /bin/bash -c " \
    npm install \
    && npm run build"


docker-compose up -d --build