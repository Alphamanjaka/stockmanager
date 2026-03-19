#!/bin/sh
set -e

# Check if $UID and $GID are set, else fallback to default (1000:1000)
USER_ID=${UID:-1000}
GROUP_ID=${GID:-1000}

# Fix file ownership and permissions using the passed UID and GID
echo "Fixing file permissions with UID=${USER_ID} and GID=${GROUP_ID}..."
chown -R ${USER_ID}:${GROUP_ID} /var/www 2>/dev/null || true

# Check if APP_KEY is set in the .env file, if not, generate it.
# This ensures the key is present and then explicitly exported to the environment
# for the php-fpm process.
if grep -q "^APP_KEY=$" /var/www/.env || ! grep -q "^APP_KEY=" /var/www/.env; then
    echo "Generating application key..."
    php /var/www/artisan key:generate
    # Read the newly generated key from the .env file and export it to the current shell's environment
    NEW_APP_KEY=$(grep "^APP_KEY=" /var/www/.env | cut -d '=' -f2)
    if [ -n "$NEW_APP_KEY" ]; then
        export APP_KEY="$NEW_APP_KEY"
        echo "Application key exported to environment."
    else
        echo "Warning: Could not read generated APP_KEY from .env file. Please check your .env file."
    fi
fi
# Export the APP_KEY from the .env file to ensure it's available in the environment for php-fpm
if grep -q "^APP_KEY=" /var/www/.env; then
    EXISTING_APP_KEY=$(grep "^APP_KEY=" /var/www/.env | cut -d '=' -f2)
    export APP_KEY="$EXISTING_APP_KEY"
    echo "Existing application key exported to environment."
else
    echo "Warning: APP_KEY not found in .env file. Please ensure it is set."
fi
# migrate database if needed
php /var/www/artisan migrate --force

# Clear configurations to avoid caching issues in development
echo "Clearing configurations..."
php /var/www/artisan config:clear
php /var/www/artisan route:clear
php /var/www/artisan view:clear

# If the command is php-fpm, run it in the foreground.
# This prevents the master process from daemonizing and losing permissions to stderr
# when running as a non-root user in Docker.
if [ "$1" = 'php-fpm' ]; then
    set -- "$@" --nodaemonize
fi

# Run the default command (e.g., php-fpm or bash)
exec "$@"
