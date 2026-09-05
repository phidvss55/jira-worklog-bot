# syntax=docker/dockerfile:1.7

FROM node:22-bookworm-slim AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts

COPY public ./public
COPY resources ./resources
COPY vite.config.js ./

RUN npm run build


FROM dunglas/frankenphp:1-php8.3-bookworm AS php-base

RUN install-php-extensions curl intl mbstring opcache zip

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer


FROM php-base AS php-dependencies

WORKDIR /app

COPY . .

RUN composer install \
    --no-dev \
    --prefer-dist \
    --classmap-authoritative \
    --no-interaction \
    --no-progress


FROM php-base AS runtime

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    LOG_LEVEL=info \
    SESSION_DRIVER=cookie \
    CACHE_STORE=array \
    QUEUE_CONNECTION=sync \
    PORT=8080

WORKDIR /app

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY --from=php-dependencies --chown=www-data:www-data /app /app
COPY --from=frontend --chown=www-data:www-data /app/public/build /app/public/build
COPY --chown=www-data:www-data docker/Caddyfile /etc/caddy/Caddyfile
COPY --chmod=755 docker/entrypoint.sh /usr/local/bin/app-entrypoint

RUN mkdir -p \
        bootstrap/cache \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
    && chown -R www-data:www-data bootstrap/cache storage /data /config

USER www-data

EXPOSE 8080

ENTRYPOINT ["app-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
