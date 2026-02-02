#!/bin/bash
set -e

# Wait for the database to be ready (optional but recommended)
# Uncomment if you want to wait for postgres
# until pg_isready -h postgres -U laravel; do
#   echo "Waiting for postgres..."
#   sleep 2
# done

# Set proper permissions for Laravel directories
chown -R www:www /var/www/storage /var/www/bootstrap/cache

# Install/update Composer dependencies if vendor doesn't exist
if [ ! -d "vendor" ]; then
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Generate application key if it doesn't exist
if [ ! -f ".env" ]; then
    cp .env.example .env
    php artisan key:generate
fi

# Run Laravel migrations (optional - be careful in dev)
# php artisan migrate --force

# Clear and cache config (optional)
php artisan config:clear
php artisan cache:clear

# Execute the main container command (CMD from Dockerfile)
exec "$@"
