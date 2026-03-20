#!/bin/sh
set -e

# --- INITIALISATION DES DROITS ---
# On s'assure que www-data peut écrire dans les dossiers vitaux
mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache bootstrap/cache
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# --- LOGIQUE PRINCIPALE (Seulement pour le service web) ---
if [ "$1" = 'php-fpm' ]; then
    echo "Checking environment configuration..."

    # 1. Génération de clé intelligente
    # On ne génère la clé QUE si APP_KEY est vide ou absente du .env
    if ! grep -q "APP_KEY=base64:" .env; then
        echo "No APP_KEY detected. Generating a new one..."
        php artisan key:generate --force
        # On force un rechargement immédiat pour que la suite du script voit la clé
        export APP_KEY=$(grep APP_KEY .env | cut -d '=' -f2)
    else
        echo "APP_KEY already exists. Skipping generation."
    fi

    # 2. Nettoyage préventif
    # On supprime les vieux caches qui pourraient causer des boucles
    php artisan config:clear
    php artisan cache:clear
    rm -rf bootstrap/cache/*.php

    # 3. Base de données
    echo "Running database migrations..."
    php artisan migrate --force

    # 4. Optimisation finale
    echo "Caching configuration and routes for production..."
    php artisan storage:link --force
    php artisan optimize
    php artisan view:cache

    echo "Production environment is ready!"
fi

# --- LANCEMENT ---
exec "$@"
