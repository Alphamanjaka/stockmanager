#!/bin/sh
set -e

# php-fpm prépare l'application avant de la servir ; le worker et le scheduler démarrent après lui (depends_on)
if [ "$1" = "php-fpm" ]; then
    php artisan migrate --force
    php artisan config:cache
    php artisan route:cache
fi

exec "$@"
