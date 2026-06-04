#!/bin/bash
set -e

# Attendre que PostgreSQL soit prêt
if [ "$DB_CONNECTION" = "pgsql" ]; then
    echo "Waiting for PostgreSQL..."
    while ! pg_isready -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME"; do
        sleep 1
    done
    echo "PostgreSQL is ready!"
fi

# Générer APP_KEY si nécessaire
if [ -z "$APP_KEY" ]; then
    echo "Generating APP_KEY..."
    php artisan key:generate --force
fi

# Run migrations (optionnel - à désactiver en production si vous les gérez autrement)
# php artisan migrate --force

# Clear caches
php artisan optimize:clear

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Créer les dossiers de logs s'ils n'existent pas
mkdir -p /var/log/supervisor
mkdir -p /var/www/html/storage/logs

# Démarrer Supervisor
echo "Starting Supervisor..."
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
