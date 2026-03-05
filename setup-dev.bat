@echo off
TITLE Setup Environnement DEV - Vitrine

echo ==========================================
echo   DEMARRAGE MODE DEVELOPPEMENT
echo ==========================================

REM 1. Gestion du fichier .env
IF NOT EXIST .env (
    IF EXIST .env.dev (
        echo [INFO] Creation de .env a partir de .env.dev
        copy .env.dev .env >nul
    ) ELSE (
        echo [INFO] Creation de .env a partir de .env.example
        copy .env.example .env >nul
    )
) ELSE (
    echo [INFO] Fichier .env deja present.
)

REM 2. Démarrage avec le fichier docker-compose de DEV
echo [INFO] Lancement des conteneurs (compose.dev.yaml)...
docker compose -f compose.dev.yaml up -d --build

REM 3. Installation des dépendances (avec require-dev)
echo [INFO] Installation des dependances Composer...
docker compose -f compose.dev.yaml exec -T php-fpm composer install

REM 4. Initialisation basique
docker compose -f compose.dev.yaml exec -T php-fpm php artisan key:generate
docker compose -f compose.dev.yaml exec -T php-fpm php artisan migrate --force --seed

echo.
echo ==========================================
echo   ENVIRONNEMENT DEV PRET !
echo ==========================================
pause
