#!/bin/sh
# Installation de StockManager depuis le paquet hors ligne (voir LISEZMOI.md)
set -e
cd "$(dirname "$0")"

echo "[1/2] Chargement des images Docker..."
docker load -i images.tar

if [ ! -f .env ]; then
    cp .env.example .env
    echo "APP_KEY=$(docker run --rm stockmanager-app php artisan key:generate --show)" >> .env
    echo "POSTGRES_PASSWORD=" >> .env
    echo
    echo "Fichier .env créé. Choisissez le mot de passe de la base : complétez la ligne POSTGRES_PASSWORD="
    echo "à la fin de .env, puis relancez sh install.sh."
    exit 0
fi

echo "[2/2] Démarrage des conteneurs..."
docker compose up -d --no-build --wait
echo
echo "StockManager est prêt : http://localhost"
