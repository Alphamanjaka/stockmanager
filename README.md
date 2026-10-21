# Vitrine - Système Avancé de Gestion de Stock & Commerciale

![PHP Version](https://img.shields.io/badge/php-8.4-blue.svg)
![Laravel Version](https://img.shields.io/badge/laravel-10.x-red.svg)
![Docker](https://img.shields.io/badge/docker-enabled-blue.svg)

**Vitrine** est une solution ERP robuste développée avec **Laravel 10** et **PHP 8.4**, conçue pour optimiser la chaîne logistique des entreprises. Au-delà d'une simple gestion de stock, elle intègre une architecture orientée services, une conteneurisation de pointe et des mécanismes de sécurité transactionnelle garantissant l'intégrité des données financières et logistiques.

## Table des matières

1.  [Points Forts & Unicité du Projet](#points-forts--unicité-du-projet)
2.  [Fonctionnalités Détaillées](#fonctionnalités-détaillées)
3.  [Architecture DevOps & Docker Multi-Stage](#architecture-devops--docker-multi-stage)
4.  [Installation et Configuration](#installation-et-configuration)
5.  [Architecture Technique (Backend)](#architecture-technique-backend)
6.  [Documentation](#documentation)

---

## Points Forts & Unicité du Projet

Ce projet se distingue par une approche industrielle de la gestion de stock :

### 🛡️ Sécurité & Fiabilité des Données

- **Transactions Atomiques** : Toutes les opérations critiques (ventes, réception de commande) sont encapsulées dans des transactions (`DB::transaction`). Cela garantit qu'aucune écriture partielle n'est effectuée en cas d'erreur.
- **Verrouillage Optimiste/Pessimiste** : Utilisation de `lockForUpdate()` lors des ventes pour empêcher qu'un même produit soit vendu deux fois simultanément (race condition).
- **Audit Log & Historique** : Traçabilité complète des mouvements de stocks (`stock_movements`). Chaque entrée ou sortie est journalisée avec sa source (Vente, Achat, Ajustement).

### 🚨 Gestion Proactive des Ruptures

- **Système d'Alerte Intelligent** : Surveillance automatisée des niveaux de stock.
- **Surveillance des pertes** : La commande `stock:monitor-drop` additionne chaque jour les pertes non expliquées (corrections en sortie, écarts d'inventaire négatifs) et alerte les administrateurs par e-mail quand elles dépassent un seuil (vol, casse, erreur de saisie). Les ventes ne déclenchent pas d'alerte.
- **Seuils Critiques** : Configuration par produit d'un seuil d'alerte déclenchant des notifications de réapprovisionnement.

### 🚀 Module d'Importation Haute Performance

- **Validation granulaire** : Détection des erreurs ligne par ligne avec rapports détaillés (champ, valeur lue, erreur).
- **Traitement des Variantes** : Gestion intelligente des produits avec ou sans variantes de couleur (intégration automatique de l'élément "No Variant").
- **Optimisation N+1** : Utilisation d'un système de cache en mémoire pendant l'import pour minimiser les requêtes vers la base de données.
- **Normalisation automatique** : Nettoyage des données (prix, stocks, formats de texte) avant validation.

### 🔍 Système de Recherche Globale (Omnisearch)

- **Architecture Hybride** : Combine une recherche instantanée côté client pour la navigation (routes statiques) et une recherche asynchrone côté serveur pour les entités métier (Produits, Achats, Ventes, Stocks).
- **Performance & Debouncing** : Implémentation d'un délai de saisie (_debounce_) de 300ms pour minimiser la charge serveur et optimiser la réactivité de l'UI.
- **Orchestration via Service Layer** : Utilisation d'un `GlobalSearchService` centralisé qui interroge les services spécialisés, garantissant une séparation stricte des responsabilités.
- **Format Standardisé** : Toutes les recherches retournent un contrat de données uniforme (`type`, `name`, `url`), facilitant l'affichage groupé et la navigation fluide.

#### 🛠️ Guide technique : Étendre la recherche à une nouvelle entité

Pour intégrer une nouvelle entité (ex: `Customer`) au système de recherche :

1.  **Backend (Service dédié)** : Implémentez la méthode `searchForGlobalSearch` dans le service de l'entité :
    ```php
    public function searchForGlobalSearch(string $query, int $limit = 5) {
        return Customer::where('name', 'like', "%$query%")
            ->limit($limit)->get()
            ->map(fn($c) => [
                'type' => 'customer', // Identifiant unique du type
                'id' => $c->id,
                'name' => $c->name,
                'url' => route('admin.customers.show', $c->id)
            ]);
    }
    ```
2.  **Enregistrement** : Injectez le nouveau service dans `GlobalSearchService` (`app/Core/Services`), appelez sa méthode dans `search()` et fusionnez les résultats dans la collection `$results`.
3.  **Frontend** : Dans `resources/js/global-search.js`, mettez à jour la logique de regroupement dans `displayResults` pour associer un libellé au nouveau type (ex: `result.type === "customer" ? "Clients"`).

### � Infrastructure Optimisée

- **Docker Multi-Stage** : Images ultra-légères basées sur Alpine Linux. Séparation stricte entre l'environnement de build (compilateurs, nodejs) et l'environnement de production (runtime PHP pur).
- **Performance** : Utilisation de **Redis** pour le cache et les files d'attente, et **PostgreSQL** pour la fiabilité des données.

---

## Fonctionnalités Détaillées

### 1. Gestion Commerciale (Ventes)

- **Point de Vente (POS)** : Interface de saisie rapide.
- **Contrôle de Stock en Temps Réel** : Impossible de valider une vente si la quantité est insuffisante.
- **Calculs Automatiques** : Gestion des remises, totaux bruts/nets et numéros de facture à la suite par année (`FAC-2026-000123`).

### 2. Chaîne d'Approvisionnement (Achats)

- **Workflow de Validation** :
    1.  Création du bon de commande (Statut : _En attente_).
    2.  Validation et réception physique.
    3.  Incrémentation automatique du stock (Statut : _Reçu_).
- **Import Excel de Masse** : Module dédié pour importer des centaines de lignes d'achats fournisseurs, avec validation de l'existence des produits et fournisseurs avant import.

### 3. Recherche Avancée & Navigation

- **Recherche d'Entités** : Localisation rapide par référence (Achats/Ventes) ou par nom/couleur (Produits).
- **Suivi des Stocks** : Recherche directe dans l'historique des mouvements de stock par raison ou produit.
- **Navigation Intuitive** : Accès rapide aux pages de configuration et de listes via la barre de recherche.

### 4. Reporting & Tableau de Bord

- Statistiques de ventes journalières.
- Valorisation du stock en temps réel.
- Historique des transactions par utilisateur.

---

## Architecture DevOps & Docker Multi-Stage

Le projet utilise une stratégie de conteneurisation avancée en **3 étapes** pour garantir sécurité et légèreté (Image finale < 100Mo hors assets) :

1.  **Build Stage (Builder)** : Image temporaire contenant tous les outils de compilation (GCC, Make, Git, Composer) pour construire les dépendances PHP.
2.  **Frontend Stage (Node)** : Compilation des assets JS/CSS (Vite/Tailwind) dans un conteneur Node.js isolé.
3.  **Production Stage (Runner)** : Image finale Alpine Linux minimale. Ne contient que le runtime PHP nécessaire, sans code source inutile (pas de `.git`, pas de `node_modules`, pas de compilateurs).

### Commandes Rapides

- **Production** : `docker compose -f compose.prod.yaml up -d --build`.
- **Développement** : `docker compose -f compose.dev.yaml up -d --build` — code monté en direct, PostgreSQL 16,
  Vite, Xdebug à la demande ; cohabite avec Laragon. Mode d'emploi complet : [docs/docker-dev.md](docs/docker-dev.md).

---

## Installation et Configuration

### Pré-requis

- Docker Desktop installé.
- Git.

### Démarrage Rapide

1.  **Cloner le projet**

    ```bash
    git clone https://github.com/votre-repo/stockmanager.git
    cd stockmanager
    ```

2.  **Lancer l'installation** (mêmes commandes sous Windows, Linux et Mac ; Docker Desktop démarré)

    ```bash
    cp .env.example .env
    docker compose -f compose.dev.yaml up -d --build
    docker compose -f compose.dev.yaml exec workspace composer install
    docker compose -f compose.dev.yaml exec workspace npm ci
    docker compose -f compose.dev.yaml exec workspace php artisan key:generate
    docker compose -f compose.dev.yaml exec workspace php artisan migrate --seed
    ```

    Avec un `.env` existant (Laragon), sauter `cp` et `key:generate` : la clé serait remplacée, ce qui ferme les
    sessions et rend illisibles les données chiffrées. Détails, tests et Xdebug : [docs/docker-dev.md](docs/docker-dev.md).

3.  **Accéder à l'application**
    - URL : `http://localhost:8080` (port réglable par `NGINX_PORT` dans le `.env`)
    - Comptes de démonstration : `admin@stock.com` / `password` (back office), `vendeur@stock.com` / `password` (vendeur)

---

## Architecture Technique (Backend)

Le code suit les principes **SOLID** et une **Architecture Orientée Services (SOA)** pour une maintenabilité maximale.

### Structure par modules

Le code métier est découpé en modules dans `app/Modules/` (**Catalog**, **Stock**, **Inventory**, **Purchase**, **Sale**, **Contact**, **Backup**), autour d'un socle commun `app/Core/` (authentification, utilisateurs, paramètres, tableaux de bord, recherche globale, import).
Chaque module contient ses modèles, services, contrôleurs, vues, routes, migrations et factories.
Conventions, règles de dépendance et recettes : voir [docs/architecture.md](docs/architecture.md).

### Couches Applicatives

1.  **Controllers** (`PurchaseController`, `SaleController`) : Reçoivent la requête HTTP, valident les entrées via des `FormRequest`, et délèguent le travail aux Services.
2.  **Services** (`SaleService`, `StockService`) : Contiennent toute la logique métier.
    - _Exemple_ : `SaleService::createSale` gère la transaction DB, vérifie le stock, crée la vente, décrémente le stock via `StockService` et génère les lignes de facture.
3.  **Models & Eloquent** : Représentation des données et relations (`Product`, `Sale`, `StockMovement`).

### Automatisation & Tâches de Fond (Cron)

Le système repose sur le scheduler Laravel pour les tâches critiques :

- **Monitoring Stock** (`stock:monitor-drop`) :
    ```bash
    # Planifié tous les jours à 18:00, sur les dernières 24 heures
    php artisan stock:monitor-drop --threshold=1 --hours=24
    ```
    Alerte les administrateurs si les pertes non expliquées de la période atteignent 1 % (paramétrable) de la
    valeur du stock, valorisée au coût d'achat (au prix de vente quand le coût n'est pas renseigné).

Pour activer les tâches planifiées en production, une seule entrée Cron est nécessaire :

```cron
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Système de Sauvegarde

Le module **Backup** est maintenant **silencieux** (aucune interface) et regroupe :

#### Archive ZIP (fichiers + base)

Archive ZIP contenant un dump SQL complet et les fichiers uploadés, gérée par `Spatie Laravel Backup`.

- Exécutée en arrière-plan via la file d'attente (pas visible dans l'interface).
- Planifiée tous les jours à **01:00 AM**.
- Historique des exécutions conservé dans la table `backup_runs`.

```bash
# Sauvegarde en arrière-plan (via la file d'attente)
php artisan app:run-backup

# Sauvegarde immédiate (Synchrone) - Idéal pour le débogage ou avant une mise à jour
php artisan app:run-backup --now
```

#### Haute disponibilité PostgreSQL (remplace le miroir)

La continuité de service en cas de panne du serveur PostgreSQL est assurée par **pg_auto_failover** avec réplication physique (streaming WAL) entre 2 nœuds (primary + standby).

- L'application se connecte via une liste multi-hôtes (`DB_HOST=nodeA,nodeB`) et `target_session_attrs=read-write`, grâce au connecteur `App\Core\Database\PostgresConnector` : la bascule est **automatique** sans changement de code.
- Un template et un guide sont fournis : `deploy/pg-ha/compose.yaml` + `docs/ha-postgresql.md`.

**Notes** : pour un vrai failover, le standby doit être sur un **serveur différent** de la prod. L'archive ZIP reste un filet de sécurité (PITR/wal-g recommandé en production HA).

---

## Documentation

La documentation détaillée est rangée dans [`docs/`](docs/) ; l'historique des versions reste dans [CHANGELOG.md](CHANGELOG.md).

| Document | Contenu |
|---|---|
| [architecture.md](docs/architecture.md) | Modules, règles de dépendance, conventions de l'interface, recettes |
| [flux-vente.md](docs/flux-vente.md) | Création d'une vente, de la caisse jusqu'à la base de données |
| [migration-variantes.md](docs/migration-variantes.md) | Variantes flexibles : conception et migration en production |
| [decisions-parametres.md](docs/decisions-parametres.md) | Système de paramètres (`settings()`, cache) |
| [api-tabulator.md](docs/api-tabulator.md) | API de la liste des achats et intégration de Tabulator |
| [docker-dev.md](docs/docker-dev.md) | Environnement Docker de développement |
| [ha-postgresql.md](docs/ha-postgresql.md) | Haute disponibilité PostgreSQL (pg_auto_failover) |
| [journal.md](docs/journal.md) | Journal de bord des évolutions |
| [analyse-ameliorations.md](docs/analyse-ameliorations.md) | Analyse du projet et plan d'amélioration |

Scripts : [`scripts/save-stockmanager.bat`](scripts/save-stockmanager.bat) prépare un paquet hors ligne des images
Docker de production (Windows, à lancer depuis n'importe quel dossier).

---

## CI/CD (Intégration Continue)

Le projet intègre un pipeline GitHub Actions complet (`.github/workflows/ci.yml`) :

1.  **Linting** : Vérification automatique du style de code (Laravel Pint).
2.  **Testing** : Exécution des tests unitaires et fonctionnels (Pest/PHPUnit).
3.  **Build & Push** : Construction de l'image Docker de production et envoi sur le registre (GHCR) uniquement si les tests passent.

---

## Dépannage

### 🔑 Erreur : "No application encryption key has been specified"

Si cette erreur apparaît après une installation Docker :

1. Assurez-vous que le fichier `.env` contient une `APP_KEY`.
2. Exécutez `docker compose exec php-fpm php artisan config:clear` pour forcer Laravel à relire l'environnement.
3. Redémarrez les conteneurs avec `docker compose up -d`.

### 💾 Problèmes de Sauvegarde

**1. Erreur : `The dump process failed with exitcode 127` (Command not found)**

Cette erreur indique que PHP ne trouve pas l'outil `pg_dump` (pour PostgreSQL) ou `mysqldump`.

- **Solution** : Dans un environnement Docker, les outils sont dans le PATH global. Vérifiez `config/database.php` et assurez-vous que `dump_binary_path` est soit `null`, soit `/usr/bin`.
    ```php
    'dump' => [
        'dump_binary_path' => '/usr/bin', // Chemin standard sur Alpine/Debian
        'use_single_transaction' => true,
        'timeout' => 60 * 5,
    ],
    ```
- **Vérification** : Lancez `docker compose exec php-fpm pg_dump --version` pour confirmer que l'outil est bien installé dans le conteneur.

**2. Permissions de Stockage**
Si le ZIP ne se crée pas, vérifiez que le dossier `storage/app/` appartient bien à l'utilisateur `www-data` (Production) ou `dev` (Local).

---

## 🎯 Réalisations Techniques (Pour CV)

Dans le cadre de ce projet, j'ai mis en œuvre des solutions à des problématiques d'ingénierie complexes :

- **Optimisation de performance** : Gestion de l'import de données avec suppression des requêtes N+1 (Cache local).
- **Haute disponibilité des données** : Utilisation de `DB::transaction` et de verrous SQL (`lockForUpdate`) pour les opérations critiques.
- **Expertise Docker** : Configuration d'images Multi-stage ultra-légères basées sur Alpine Linux.
- **Clean Code** : Application rigoureuse des principes SOLID et du Service Layer Pattern.
- **Automatisation DevOps** : Scripting d'installation (`.bat`) et intégration continue (CI).

---

_Développé avec ❤️ pour une gestion de stock sans faille._
