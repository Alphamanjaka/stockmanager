# StockManager — installation hors ligne

Prérequis : Docker démarré (Docker Desktop sous Windows et Mac), 2 Go d'espace disque.

1. Extraire l'archive.
2. Lancer `install.bat` (Windows) ou `sh install.sh` (Linux, Mac) : il charge les images et crée le fichier `.env`
   avec une clé d'application.
3. Compléter la ligne `POSTGRES_PASSWORD=` à la fin de `.env` : mot de passe de la base, choisi librement, à conserver.
4. Relancer `install.bat` ou `sh install.sh`, puis ouvrir http://localhost.

La base est créée et mise à jour au démarrage. Pour un autre port que 80 : `NGINX_PORT=8080` dans `.env`.

| Besoin | Commande |
|---|---|
| Arrêter | `docker compose stop` |
| Redémarrer | `docker compose up -d` |
| Journaux | `docker compose logs -f` |
| Console Laravel | `docker compose exec php-fpm php artisan tinker` |

La base et les fichiers envoyés (logo…) sont dans des volumes Docker : `docker compose down` les conserve,
`docker compose down -v` les **supprime**.
