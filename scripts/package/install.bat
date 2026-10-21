@echo off
REM Installation de StockManager depuis le paquet hors ligne (voir LISEZMOI.md)
cd /d "%~dp0"

echo [1/2] Chargement des images Docker...
docker load -i images.tar || goto erreur

if not exist .env (
    copy .env.example .env >nul
    for /f "delims=" %%k in ('docker run --rm stockmanager-app php artisan key:generate --show') do >>.env echo APP_KEY=%%k
    >>.env echo POSTGRES_PASSWORD=
    echo.
    echo Fichier .env cree. Choisissez le mot de passe de la base : completez la ligne POSTGRES_PASSWORD=
    echo a la fin de .env, puis relancez install.bat.
    pause
    exit /b 0
)

echo [2/2] Demarrage des conteneurs...
docker compose up -d --no-build --wait || goto erreur
echo.
echo StockManager est pret : http://localhost
pause
exit /b 0

:erreur
echo.
echo [ERREUR] Voir les messages ci-dessus. Docker Desktop est-il demarre ?
pause
exit /b 1
