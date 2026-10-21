# Développer avec Docker

L'environnement de développement Docker (`compose.dev.yaml`) fait tourner l'application comme en production
(PHP-FPM + Nginx + PostgreSQL 16 + Redis), avec le code monté en direct : une modification faite dans VS Code est
visible immédiatement, sans reconstruire.

Il **cohabite avec Laragon** : ports différents, même fichier `.env`, `node_modules` séparé.

| Service | Rôle | Accès depuis Windows |
|---|---|---|
| `web` | Nginx | http://localhost:8080 |
| `php-fpm` | PHP 8.4 qui sert l'application | — |
| `workspace` | Terminal de travail : composer, artisan, npm (Vite), tests — même image que `php-fpm` | Vite : http://localhost:5173 |
| `postgres` | PostgreSQL 16 (base `app`, utilisateur `laravel` / `secret`) | `localhost:5433` (DBeaver, pgAdmin…) |
| `redis` | Redis | — |

Ports réglables dans `.env` : `NGINX_PORT`, `POSTGRES_PORT`, `VITE_PORT`.

## Première installation

Prérequis : Docker Desktop (moteur WSL2) démarré.

```bash
# 1. Construire et démarrer (quelques minutes la première fois)
docker compose -f compose.dev.yaml up -d --build

# 2. Dépendances, dans le conteneur de travail
docker compose -f compose.dev.yaml exec workspace composer install
docker compose -f compose.dev.yaml exec workspace npm ci

# 3. Base de données de développement (données de démonstration)
docker compose -f compose.dev.yaml exec workspace php artisan migrate --seed
```

> **Venant de l'ancienne configuration** (workspace sous Debian) : son volume `node_modules`, créé au nom de root, est
> resté vide (`npm ci` y échouait). Le supprimer une fois, avant de reconstruire, puis refaire `npm ci` :
> `docker compose -f compose.dev.yaml rm -sf workspace` puis
> `docker volume rm stockmanager-dev_node-modules-development`.

Ouvrir http://localhost:8080 — comptes de démonstration : `admin@stock.com` / `password` (back office),
`vendeur@stock.com` / `password` (vendeur).

> Le `.env` reste celui de Laragon : `compose.dev.yaml` impose aux conteneurs les paramètres propres à Docker
> (`DB_HOST=postgres`, `DB_USERNAME=laravel`, `REDIS_HOST=redis`…). Si `APP_KEY` est vide :
> `docker compose -f compose.dev.yaml exec workspace php artisan key:generate`.

## Au quotidien

```bash
docker compose -f compose.dev.yaml up -d          # démarrer
docker compose -f compose.dev.yaml stop           # arrêter (les données sont conservées)
```

Pour éviter de retaper `-f compose.dev.yaml`, définir une fois `COMPOSE_FILE=compose.dev.yaml` dans le `.env` :
toutes les commandes deviennent `docker compose …`.

| Besoin | Commande |
|---|---|
| Terminal dans le conteneur | `docker compose -f compose.dev.yaml exec workspace bash` |
| Artisan | `docker compose -f compose.dev.yaml exec workspace php artisan <commande>` |
| CSS/JS rechargés à chaud | `docker compose -f compose.dev.yaml exec workspace npm run dev` (laisser tourner) |
| Compiler les assets une fois | `docker compose -f compose.dev.yaml exec workspace npm run build` |
| Logs Laravel | `storage/logs/laravel.log` (fichier partagé) |
| Logs des conteneurs | `docker compose -f compose.dev.yaml logs -f web php-fpm` |
| File d'attente (sauvegardes…) | `docker compose -f compose.dev.yaml exec workspace php artisan queue:work` |

Quand `npm run dev` tourne, il crée `public/hot` et le site charge les assets depuis Vite ; à l'arrêt, le fichier est
supprimé et le site revient aux assets compilés (`public/build`).

Le code est partagé entre Windows (Laragon) et les conteneurs. **Ne pas mettre la configuration en cache sous Windows**
(`php artisan config:cache`, `optimize`) avant de lancer les conteneurs : ses chemins `C:\...` y sont lus comme des
chemins relatifs. Des vues ont déjà été compilées ainsi dans `public/`, où le serveur web les servait. En cas de doute :
`php artisan optimize:clear`.

## Tests

Les tests **vident la base** : ils refusent de tourner sur la base `app` (garde-fou dans `tests/TestCase.php`).

```bash
# SQLite en mémoire (rapide)
docker compose -f compose.dev.yaml exec -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: workspace php artisan test

# PostgreSQL 16, comme la production : base dédiée « testing » (à créer une fois)
docker compose -f compose.dev.yaml exec postgres createdb -U laravel testing
docker compose -f compose.dev.yaml exec -e DB_DATABASE=testing workspace php artisan test
```

## Déboguer avec Xdebug

Désactivé par défaut (il ralentit chaque requête). Pour l'activer : `XDEBUG_ENABLED=true` dans le `.env`, puis
`docker compose -f compose.dev.yaml up -d --build`. Port 9003, chemin du projet dans le conteneur : `/var/www`.

## Repartir de zéro

```bash
docker compose -f compose.dev.yaml down       # supprime les conteneurs, garde la base
docker compose -f compose.dev.yaml down -v    # supprime aussi la base et node_modules du conteneur
```

## Performances

Le code est sur le disque Windows (`C:\laragon\www`) : chaque accès fichier depuis le conteneur traverse la couche de
partage de Docker Desktop, ce qui rend les pages plus lentes qu'avec Laragon. Pour un environnement Docker rapide,
cloner le projet dans le système de fichiers WSL (ex. `\\wsl$\Ubuntu\home\<vous>\stockmanager`) et l'ouvrir avec
l'extension VS Code « WSL ».
