@echo off
TITLE Installation Projet Vitrine

echo ==========================================
echo   INSTALLATION DU PROJET VITRINE
echo ==========================================

REM 1. Vérification de Docker
docker --version >nul 2>&1
IF %ERRORLEVEL% NEQ 0 (
    echo [ERREUR] Docker n'est pas lance ou installe.
    echo Veuillez lancer Docker Desktop et reessayer.
    pause
    exit /b
)

REM 2. Configuration de l'environnement
IF NOT EXIST .env (
    IF EXIST .env.prod (
        echo [1/6] Configuration : Utilisation du fichier .env.prod...
        copy .env.prod .env >nul
    ) ELSE (
        echo [1/6] .env.prod introuvable. Generation automatique depuis .env.example...
        copy .env.example .env >nul
        powershell -Command "(Get-Content .env) -replace 'APP_ENV=local', 'APP_ENV=production' -replace 'APP_DEBUG=true', 'APP_DEBUG=false' | Set-Content .env"
    )
) ELSE (
    echo [1/6] Fichier .env deja present.
)

REM 3. Démarrage des conteneurs
echo [2/6] Construction et demarrage des conteneurs (cela peut prendre quelques minutes)...
REM Utilisation explicite du fichier de production
REM Reconstruction complete pour eviter les problemes de cache Docker
docker compose -f compose.prod.yaml build --no-cache
docker compose -f compose.prod.yaml up -d

REM 4. Installation des dépendances PHP
echo [3/6] Installation des dependances...
REM Les dependances PHP (vendor) sont installees lors de l'etape de build de l'image.
REM Il n'est pas necessaire de les reinstaller ici. L'executable composer n'est d'ailleurs
REM pas inclus dans l'image de production finale pour des raisons de securite et de taille.
echo    - Dependances deja installees dans l'image Docker.

REM 5. Initialisation Laravel
echo [4/6] Initialisation de la base de donnees et des cles...
hedREM On nettoie les caches pour eviter les erreurs liees a des paquets de developpement (comme Pail)
docker compose -f compose.prod.yaml exec -T php-fpm php artisan config:clear
docker compose -f compose.prod.yaml exec -T php-fpm php artisan key:generate
docker compose -f compose.prod.yaml exec -T php-fpm php artisan storage:link
docker compose -f compose.prod.yaml exec -T php-fpm php artisan migrate --force
REM Creation du dossier pour les vues compilees pour eviter l'erreur "View path not found" lors de l'optimisation
docker compose -f compose.prod.yaml exec -T php-fpm mkdir -p storage/framework/views
REM Optimisation : Mise en cache de la config, des routes et des vues pour la vitesse
docker compose -f compose.prod.yaml exec -T php-fpm php artisan optimize
docker compose -f compose.prod.yaml exec -T php-fpm php artisan view:cache

REM 6. Récupération des assets (CSS/JS)
echo [5/6] Recuperation des fichiers CSS/JS compiles...
REM Cette étape est CRUCIALE car le volume Windows masque le dossier public/build construit dans l'image
docker compose -f compose.prod.yaml cp php-fpm:/var/www/public/build ./public/

echo.
echo ==========================================
echo   INSTALLATION TERMINEE AVEC SUCCES !
echo ==========================================
echo.
echo L'application est accessible sur : http://localhost:8000
echo (ou le port defini dans votre docker-compose.yml)
echo.
pause
