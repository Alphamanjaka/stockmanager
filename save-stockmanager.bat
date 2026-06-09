@echo off
setlocal enabledelayedexpansion

echo ========================================
echo Sauvegarde du projet StockManager
echo ========================================
echo.

set DATE=%date:~-4,4%%date:~-7,2%%date:~-10,2%
set PACKAGE_NAME=stockmanager-%DATE%
set PACKAGE_DIR=%PACKAGE_NAME%

echo Verification des images Docker...
docker image inspect stockmanager-php-fpm:latest >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERREUR] Les images Docker ne semblent pas construites. 
    echo Lancez 'docker compose -f compose.prod.yaml build' d'abord.
    pause
    exit /b
)

echo Création du dossier %PACKAGE_DIR%...
if exist %PACKAGE_DIR% rmdir /s /q %PACKAGE_DIR%
mkdir %PACKAGE_DIR% 2>nul
mkdir %PACKAGE_DIR%\images 2>nul

echo.
echo [1/4] Sauvegarde des images Docker...
echo ----------------------------------------

echo Sauvegarde de l'image php-fpm...
docker save stockmanager-php-fpm:latest -o %PACKAGE_DIR%\images\php-fpm.tar

echo Sauvegarde de l'image web (nginx)...
docker save stockmanager-web:latest -o %PACKAGE_DIR%\images\web.tar

echo Sauvegarde du scheduler...
docker save stockmanager-scheduler:latest -o %PACKAGE_DIR%\images\scheduler.tar

echo Sauvegarde du worker...
docker save stockmanager-worker:latest -o %PACKAGE_DIR%\images\worker.tar

echo Sauvegarde de PostgreSQL...
docker pull postgres:16 2>nul
docker save postgres:16 -o %PACKAGE_DIR%\images\postgres-16.tar

echo Sauvegarde de Redis...
docker pull redis:alpine 2>nul
docker save redis:alpine -o %PACKAGE_DIR%\images\redis-alpine.tar

echo.
echo [2/4] Copie des fichiers de configuration...
echo ----------------------------------------

REM Créer un docker-compose.yml propre (sans build)
(
echo version: '3.8'
echo.
echo services:
echo   php-fpm:
echo     image: stockmanager-php-fpm:latest
echo     container_name: stockmanager-php-fpm
echo     restart: unless-stopped
echo     volumes:
echo       - storage_data:/var/www/html/storage
echo     networks:
echo       - stockmanager_network
echo.
echo   web:
echo     image: stockmanager-web:latest
echo     container_name: stockmanager-web
echo     restart: unless-stopped
echo     ports:
echo       - "80:80"
echo     depends_on:
echo       - php-fpm
echo     networks:
echo       - stockmanager_network
echo.
echo   postgres:
echo     image: postgres:16
echo     container_name: stockmanager-postgres
echo     restart: unless-stopped
echo     environment:
echo       POSTGRES_DB: stockmanager
echo       POSTGRES_USER: laravel
echo       POSTGRES_PASSWORD: secret
echo     volumes:
echo       - postgres_data:/var/lib/postgresql/data
echo     networks:
echo       - stockmanager_network
echo.
echo   redis:
echo     image: redis:alpine
echo     container_name: stockmanager-redis
echo     restart: unless-stopped
echo     volumes:
echo       - redis_data:/data
echo     networks:
echo       - stockmanager_network
echo.
echo   scheduler:
echo     image: stockmanager-scheduler:latest
echo     container_name: stockmanager-scheduler
echo     restart: unless-stopped
echo     depends_on:
echo       - php-fpm
echo     networks:
echo       - stockmanager_network
echo.
echo   worker:
echo     image: stockmanager-worker:latest
echo     container_name: stockmanager-worker
echo     restart: unless-stopped
echo     depends_on:
echo       - php-fpm
echo     networks:
echo       - stockmanager_network
echo.
echo networks:
echo   stockmanager_network:
echo     driver: bridge
echo.
echo volumes:
echo   storage_data:
echo   postgres_data:
echo   redis_data:
) > %PACKAGE_DIR%\docker-compose.yml

REM Copier .env.example s'il existe
if exist .env (
    copy .env %PACKAGE_DIR%\.env.example
) else (
    echo APP_ENV=production > %PACKAGE_DIR%\.env.example
    echo APP_DEBUG=false >> %PACKAGE_DIR%\.env.example
    echo APP_KEY= >> %PACKAGE_DIR%\.env.example
)

echo.
echo [3/4] Création des scripts d'installation...
echo ----------------------------------------

REM install.bat (charge les images et démarre)
(
echo @echo off
echo echo ========================================
echo echo Installation de StockManager
echo echo ========================================
echo echo.
echo echo [1/2] Chargement des images Docker...
echo echo.
echo if not exist images\ (
echo     echo Erreur: Dossier 'images' introuvable
echo     pause
echo     exit /b 1
echo ^)
echo.
echo for %%%%f in ^(images\*.tar^) do ^(
echo     echo Chargement de %%%%~nf...
echo     docker load -i "%%%%f"
echo     if errorlevel 1 ^(
echo         echo Erreur lors du chargement de %%%%~nf
echo         pause
echo         exit /b 1
echo     ^)
echo ^)
echo echo.
echo echo [2/2] Demarrage des conteneurs...
echo docker compose up -d
echo echo.
echo echo ========================================
echo echo Installation terminee !
echo echo ========================================
echo echo Lancez setup.bat pour finaliser
echo echo.
echo pause
) > %PACKAGE_DIR%\install.bat

REM setup.bat (configure l'application)
(
echo @echo off
echo echo ========================================
echo echo Configuration de StockManager
echo echo ========================================
echo echo.
echo echo [1/5] Copie du fichier .env...
echo if not exist .env (
echo     if exist .env.example (
echo         copy .env.example .env
echo         echo Fichier .env cree
echo     ^) else ^(
echo         echo Erreur: .env.example introuvable
echo         pause
echo         exit /b 1
echo     ^)
echo ^) else ^(
echo     echo .env existe deja
echo ^)
echo.
echo echo [2/5] Attente du demarrage des services...
echo timeout /t 10 /nobreak ^>nul
echo.
echo echo [3/5] Generation de la cle application...
echo docker compose exec -T php-fpm php artisan key:generate
echo if errorlevel 1 ^(
echo     echo Erreur: PHP-FPM n'est pas pret
echo     echo Attendez quelques secondes et relancez ce script
echo     pause
echo     exit /b 1
echo ^)
echo.
echo echo [4/5] Migration de la base de donnees...
echo docker compose exec -T php-fpm php artisan migrate --force
echo.
echo echo [5/5] Optimisation du cache...
echo docker compose exec -T php-fpm php artisan config:cache
echo docker compose exec -T php-fpm php artisan route:cache
echo docker compose exec -T php-fpm php artisan view:cache
echo.
echo echo ========================================
echo echo ✅ Application prete !
echo echo ========================================
echo echo Accedez a : http://localhost
echo echo.
echo echo Pour arreter : docker compose down
echo echo Pour les logs : docker compose logs -f
echo echo.
echo pause
) > %PACKAGE_DIR%\setup.bat

REM install.sh pour Linux/Mac
(
echo #!/bin/bash
echo echo "========================================"
echo echo "Installation de StockManager"
echo echo "========================================"
echo echo ""
echo echo "[1/2] Chargement des images Docker..."
echo echo ""
echo for img in images/*.tar; do
echo     echo "Chargement de $(basename "$img")..."
echo     docker load -i "$img"
echo done
echo echo ""
echo echo "[2/2] Demarrage des conteneurs..."
echo docker compose up -d
echo echo ""
echo echo "========================================"
echo echo "Installation terminee !"
echo echo "========================================"
echo echo "Lancez ./setup.sh pour finaliser"
) > %PACKAGE_DIR%\install.sh

REM setup.sh pour Linux/Mac
(
echo #!/bin/bash
echo echo "========================================"
echo echo "Configuration de StockManager"
echo echo "========================================"
echo echo ""
echo echo "[1/4] Copie du fichier .env..."
echo if [ ! -f .env ]; then
echo     cp .env.example .env
echo     echo "Fichier .env cree"
echo fi
echo echo ""
echo echo "[2/4] Attente du demarrage..."
echo sleep 10
echo echo ""
echo echo "[3/4] Generation de la cle..."
echo docker compose exec -T php-fpm php artisan key:generate
echo echo ""
echo echo "[4/4] Migration de la base..."
echo docker compose exec -T php-fpm php artisan migrate --force
echo echo ""
echo echo "========================================"
echo echo "✅ Application prete !"
echo echo "========================================"
echo echo "Accedez a : http://localhost"
) > %PACKAGE_DIR%\setup.sh

REM README
(
echo # StockManager - Application de gestion de stock
echo.
echo ## 📋 Description
echo Application Dockerisee avec Laravel, PostgreSQL et Redis.
echo.
echo ## 🚀 Installation
echo.
echo ### Prérequis
echo - Docker Desktop installe et demarre
echo - 2 Go d'espace disque disponible
echo.
echo ### Windows
echo 1. Extraire cette archive
echo 2. Double-cliquer sur `install.bat`
echo 3. Attendre la fin (2-5 minutes)
echo 4. Double-cliquer sur `setup.bat`
echo 5. Ouvrir http://localhost
echo.
echo ### Linux/Mac
echo ```bash
echo chmod +x install.sh setup.sh
echo ./install.sh
echo ./setup.sh
echo ```
echo.
echo ## 🔧 Services inclus
echo - PHP-FPM 8.x
echo - Nginx
echo - PostgreSQL 16
echo - Redis
echo - Laravel Scheduler
echo - Laravel Worker
echo.
echo ## ⚙️ Commandes utiles
echo ```bash
echo docker compose down      # Arreter
echo docker compose up -d     # Redemarrer
echo docker compose logs -f   # Voir les logs
echo docker compose exec php-fpm php artisan tinker  # Console Laravel
echo ```
echo.
echo ## 📞 Support
echo Contacter l'administrateur
) > %PACKAGE_DIR%\README.md

echo.
echo [4/4] Compression du package...
echo ----------------------------------------
tar -czf %PACKAGE_NAME%.tar.gz %PACKAGE_DIR%

echo.
echo ========================================
echo ✅ Sauvegarde terminee !
echo ========================================
echo Fichier cree : %PACKAGE_NAME%.tar.gz
for %%A in (%PACKAGE_NAME%.tar.gz) do echo Taille : %%~zA bytes
echo.
echo A envoyer a l'utilisateur : %PACKAGE_NAME%.tar.gz
echo.

REM Nettoyage
rmdir /s /q %PACKAGE_DIR% 2>nul
pause