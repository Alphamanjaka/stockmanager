@echo off
setlocal
REM Prepare un paquet hors ligne de StockManager : images Docker de production, compose.yaml et scripts
REM d'installation (scripts\package). Le script est range dans scripts\ : il travaille depuis la racine du projet.
cd /d "%~dp0.."

set DATE=%date:~-4,4%%date:~-7,2%%date:~-10,2%
set PACKAGE=stockmanager-%DATE%

echo [1/3] Construction des images de production (code actuel)...
docker build -f docker\Dockerfile --target app -t stockmanager-app . || goto erreur
docker build -f docker\Dockerfile --target web -t stockmanager-web . || goto erreur
REM Hors ligne, les versions deja presentes sur le poste servent
docker pull postgres:16 >nul 2>&1
docker pull redis:alpine >nul 2>&1

if exist %PACKAGE% rmdir /s /q %PACKAGE%
mkdir %PACKAGE%

echo [2/3] Export des images...
docker save stockmanager-app stockmanager-web postgres:16 redis:alpine -o %PACKAGE%\images.tar || goto erreur

echo [3/3] Fichiers d'installation et archive...
copy compose.prod.yaml %PACKAGE%\compose.yaml >nul
REM Modele sans secret : install.bat y ajoute une cle d'application neuve
findstr /v /b /c:"APP_KEY=" .env.example > %PACKAGE%\.env.example
copy scripts\package\* %PACKAGE%\ >nul
tar -czf %PACKAGE%.tar.gz %PACKAGE% || goto erreur
rmdir /s /q %PACKAGE%

echo.
echo Paquet cree : %PACKAGE%.tar.gz (installation : LISEZMOI.md dans l'archive)
pause
exit /b 0

:erreur
echo.
echo [ERREUR] Voir les messages ci-dessus. Docker Desktop est-il demarre ?
pause
exit /b 1
