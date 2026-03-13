#!/bin/sh
set -e

# Ce script s'exécute à chaque démarrage du conteneur.

echo "Running application entrypoint script..."

# 1. Préparation des dossiers et permissions
# On s'assure que les dossiers de cache existent et que www-data peut y écrire
mkdir -p storage/framework/views
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# 2. Nettoyage et Optimisation
# On efface d'abord la config pour éviter les conflits
php artisan config:clear
# On met en cache la config, les routes et les vues pour la performance
# (optimize inclut config:cache et route:cache)
php artisan optimize
php artisan view:cache

# Exécute les migrations de la base de données.
# L'option --force est nécessaire car l'environnement est détecté comme 'production'.
php artisan migrate --force

# Exécute la commande principale du Dockerfile (CMD), qui est "apache2-foreground".
exec "$@"
