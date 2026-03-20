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
    )
) ELSE (
    echo [1/6] Fichier .env deja present. (Supprimez-le manuellement pour reinitialiser depuis .env.prod)
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
REM Generation de la cle d'application.

REM Assurer la presence de APP_KEY dans le fichier .env via PowerShell (plus robuste pour l'encodage)
REM Cela ajoute la ligne APP_KEY= si elle est manquante, permettant a artisan key:generate de fonctionner
powershell -Command "if (!(Select-String -Path .env -Pattern '^APP_KEY=' -Quiet)) { Add-Content -Path .env -Value 'APP_KEY=' -Encoding ASCII }"

docker compose -f compose.prod.yaml exec -T php-fpm php artisan key:generate --force

REM Redemarrage du conteneur PHP pour qu'il prenne en compte la nouvelle cle dans son environnement.
echo    - Redemarrage du conteneur PHP pour charger la nouvelle cle...
docker compose -f compose.prod.yaml restart php-fpm
REM Note : Les optimisations (cache, permissions, migrations) sont maintenant
REM executees automatiquement par le script entrypoint.sh au demarrage.

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
