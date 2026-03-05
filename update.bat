@echo off
TITLE Mise a jour Projet Vitrine

echo ==========================================
echo   MISE A JOUR DU PROJET VITRINE
echo ==========================================

REM 1. Vérification de Docker
docker --version >nul 2>&1
IF %ERRORLEVEL% NEQ 0 (
    echo [ERREUR] Docker n'est pas lance ou installe.
    echo Veuillez lancer Docker Desktop et reessayer.
    pause
    exit /b
)

REM 2. Mise à jour du code source (Git)
echo [1/6] Recuperation des modifications (Git pull)...
git pull
IF %ERRORLEVEL% NEQ 0 (
    echo [ATTENTION] Echec du git pull.
    echo Assurez-vous que Git est installe et que vous avez acces au depot.
    echo Si vous avez mis a jour les fichiers manuellement (zip), ignorez ce message.
)

REM 3. Reconstruction des conteneurs
echo [2/6] Reconstruction des conteneurs (Integration du nouveau code)...
REM L'option --build est obligatoire car le code est copie dans l'image en prod
docker compose -f compose.prod.yaml up -d --build --remove-orphans

REM 4. Mise à jour des dépendances PHP
echo [3/6] Verification des dependances PHP...
docker compose -f compose.prod.yaml exec -T php-fpm composer install --no-dev --optimize-autoloader --no-scripts

REM 5. Base de données
echo [4/6] Mise a jour de la base de donnees...
docker compose -f compose.prod.yaml exec -T php-fpm php artisan migrate --force

REM 6. Optimisation (Cache)
echo [5/6] Regeneration des caches de performance...
docker compose -f compose.prod.yaml exec -T php-fpm php artisan optimize
docker compose -f compose.prod.yaml exec -T php-fpm php artisan view:cache

REM 7. Assets
echo [6/6] Extraction des nouveaux assets compiles (CSS/JS)...
docker compose -f compose.prod.yaml cp php-fpm:/var/www/public/build ./public/

echo.
echo ==========================================
echo   MISE A JOUR TERMINEE AVEC SUCCES !
echo ==========================================
pause
