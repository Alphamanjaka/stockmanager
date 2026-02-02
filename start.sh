#!/bin/sh
set -e

# Remplace le port 8080 par le port dynamique de Railway dans le fichier nginx.conf
if [ -n "$PORT" ]; then
    sed -i "s/listen 8080;/listen ${PORT};/" /etc/nginx/conf.d/default.conf
fi

# Optimisations Laravel
if [ "$APP_ENV" != "local" ]; then
    echo "Mise en cache de la configuration pour la production..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# Migrations
php artisan migrate --force

# Démarrer PHP-FPM en arrière-plan
php-fpm -D

# Démarrer Nginx au premier plan
echo "Démarrage de Nginx sur le port ${PORT:-8080}..."
nginx -g 'daemon off;'
