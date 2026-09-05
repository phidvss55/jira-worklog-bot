#!/bin/sh
set -eu

if [ -z "${APP_KEY:-}" ]; then
    if [ "${APP_KEY_AUTO_GENERATE:-false}" != "true" ]; then
        echo "APP_KEY must be supplied at runtime." >&2
        exit 1
    fi

    app_key_file="${APP_KEY_FILE:-/data/app-key}"

    if [ ! -s "$app_key_file" ]; then
        umask 077
        php -r 'echo "base64:", base64_encode(random_bytes(32)), PHP_EOL;' > "$app_key_file"
    fi

    APP_KEY="$(tr -d '\r\n' < "$app_key_file")"
    export APP_KEY
fi

mkdir -p \
    bootstrap/cache \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

php artisan config:cache --no-interaction
php artisan view:cache --no-interaction

exec docker-php-entrypoint "$@"
