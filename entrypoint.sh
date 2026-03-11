#!/bin/sh
set -e

# Ce script s'exécute à chaque démarrage du conteneur.

echo "Running application entrypoint script..."

# Exécute les migrations de la base de données.
# L'option --force est nécessaire car l'environnement est détecté comme 'production'.
php artisan migrate --force

# Exécute la commande principale du Dockerfile (CMD), qui est "apache2-foreground".
exec "$@"
