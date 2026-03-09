@echo off
TITLE Publication sur Docker Hub - Vitrine

SET /P DOCKER_USERNAME="Entrez votre nom d'utilisateur Docker Hub : "
IF "%DOCKER_USERNAME%"=="" (
    echo [ERREUR] Le nom d'utilisateur Docker Hub est requis.
    pause
    exit /b
)

SET IMAGE_NAME=vitrine-app
SET IMAGE_TAG=latest
SET DOCKER_REPO=%DOCKER_USERNAME%/%IMAGE_NAME%

echo ==========================================
echo   PUBLICATION SUR DOCKER HUB
echo ==========================================
echo.
echo Depot cible : %DOCKER_REPO%:%IMAGE_TAG%
echo.

REM 1. Vérification de Docker
docker --version >nul 2>&1
IF %ERRORLEVEL% NEQ 0 (
    echo [ERREUR] Docker n'est pas lance ou installe.
    echo Veuillez lancer Docker Desktop et reessayer.
    pause
    exit /b
)

REM 2. Connexion à Docker Hub
echo [1/4] Connexion a Docker Hub...
docker login -u %DOCKER_USERNAME%
IF %ERRORLEVEL% NEQ 0 (
    echo [ERREUR] La connexion a Docker Hub a echoue.
    pause
    exit /b
)

REM 3. Construction de l'image de production
echo [2/4] Construction de l'image de l'application...
docker compose -f compose.prod.yaml build php-fpm

REM 4. Tag de l'image pour Docker Hub
echo [3/4] Tag de l'image pour Docker Hub...
REM Docker Compose nomme l'image <dossier_projet>-<nom_service> par defaut.
docker tag vitrine-php-fpm %DOCKER_REPO%:%IMAGE_TAG%

REM 5. Push de l'image sur Docker Hub
echo [4/4] Envoi de l'image vers Docker Hub...
docker push %DOCKER_REPO%:%IMAGE_TAG%

echo.
echo ==========================================
echo   IMAGE PUBLIEE AVEC SUCCES !
echo ==========================================
echo Depot : %DOCKER_REPO%:%IMAGE_TAG%
echo.
pause
