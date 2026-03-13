#!/bin/sh
set -e

# Initialize storage directory if empty
# -----------------------------------------------------------
# If the storage directory is empty, copy the initial contents
# and set the correct permissions.
# -----------------------------------------------------------
if [ ! "$(ls -A /var/www/storage)" ]; then
  echo "Initializing storage directory..."
  cp -R /var/www/storage-init/. /var/www/storage
  chown -R www-data:www-data /var/www/storage
fi

# Remove storage-init directory
rm -rf /var/www/storage-init

# 1. Préparation des dossiers et permissions
# On s'assure que les dossiers de cache existent et que www-data peut y écrire
mkdir -p storage/framework/views
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Run Laravel migrations
# -----------------------------------------------------------
# Ensure the database schema is up to date.
# -----------------------------------------------------------
php artisan migrate --force

# Clear and cache configurations
# -----------------------------------------------------------
# Improves performance by caching config and routes.
# -----------------------------------------------------------
# On nettoie d'abord la configuration pour éviter les conflits
php artisan config:clear

# On lance les optimisations (optimize inclut config:cache et route:cache)
php artisan storage:link
php artisan optimize
php artisan view:cache
# Run the default command
exec "$@"
