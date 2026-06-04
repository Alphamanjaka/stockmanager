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
    echo Si vous avez mis a jour les fichiers manuellement ^(zip^), ignorez ce message.
)

REM 3. Reconstruction des conteneurs
echo [2/6] Reconstruction des conteneurs (Integration du nouveau code)...
REM L'option --build est obligatoire car le code est copie dans l'image en prod
REM On retire --no-cache pour que Docker reutilise les couches (vendor, os) si elles n'ont pas change.
docker compose -f compose.prod.yaml up -d --build --remove-orphans

REM 4. Mise à jour des dépendances PHP
echo [3/6] Verification des dependances PHP...
REM Les dependances PHP (vendor) sont mises a jour lors de l'etape de build de l'image.
echo    - Dependances mises a jour dans l'image Docker.

REM 5. Base de données
echo [4/6] Mise a jour de la base de donnees...
REM Note : Les migrations et optimisations sont maintenant executees
REM automatiquement par le conteneur au demarrage (entrypoint).

REM 6. Assets - Compilation frontend
echo [5/6] Compilation des assets frontend (CSS/JS)...

REM Verifier que Node.js est installe
node --version >nul 2>&1
IF %ERRORLEVEL% NEQ 0 (
    echo [ERREUR] Node.js n'est pas installe sur votre machine.
    echo Veuillez installer Node.js (https://nodejs.org) et relancer update.bat
    pause
    exit /b
)

echo    - Installation des dependances NPM (localement)...
call npm install
IF %ERRORLEVEL% NEQ 0 (
    echo [ERREUR] Echec npm install
    pause
    exit /b
)

echo    - Build des assets Vite (localement)...
call npm run build
IF %ERRORLEVEL% NEQ 0 (
    echo [ERREUR] Echec npm run build
    pause
    exit /b
)

echo    - Copie des assets compiles vers le conteneur Docker...
docker compose -f compose.prod.yaml cp ./public/build/. php-fpm:/var/www/public/build/
IF %ERRORLEVEL% NEQ 0 (
    echo [ERREUR] Echec copie des assets vers le conteneur
    echo Cela peut arriver si le conteneur n'est pas en cours d'execution.
    echo Les assets seront a jour localement, mais peut-etre pas dans Docker.
)

echo.
echo ==========================================
echo   MISE A JOUR TERMINEE AVEC SUCCES !
echo ==========================================
echo.
echo [INFO] Les assets CSS/JS sont maintenant a jour.
echo       Le conteneur web rechargera les assets au prochain refresh.
pause
