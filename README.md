# Vitrine - Système Avancé de Gestion de Stock & Commerciale

**Vitrine** est une solution ERP robuste développée avec **Laravel 10** et **PHP 8.4**, conçue pour optimiser la chaîne logistique des entreprises. Au-delà d'une simple gestion de stock, elle intègre une architecture orientée services, une conteneurisation de pointe et des mécanismes de sécurité transactionnelle garantissant l'intégrité des données financières et logistiques.

## Table des matières

1.  [Points Forts & Unicité du Projet](#points-forts--unicité-du-projet)
2.  [Fonctionnalités Détaillées](#fonctionnalités-détaillées)
3.  [Architecture DevOps & Docker Multi-Stage](#architecture-devops--docker-multi-stage)
4.  [Installation et Configuration](#installation-et-configuration)
5.  [Architecture Technique (Backend)](#architecture-technique-backend)

---

## Points Forts & Unicité du Projet

Ce projet se distingue par une approche industrielle de la gestion de stock :

### 🛡️ Sécurité & Fiabilité des Données

- **Transactions Atomiques** : Toutes les opérations critiques (ventes, réception de commande) sont encapsulées dans des transactions (`DB::transaction`). Cela garantit qu'aucune écriture partielle n'est effectuée en cas d'erreur.
- **Verrouillage Optimiste/Pessimiste** : Utilisation de `lockForUpdate()` lors des ventes pour empêcher qu'un même produit soit vendu deux fois simultanément (race condition).
- **Audit Log & Historique** : Traçabilité complète des mouvements de stocks (`stock_movements`). Chaque entrée ou sortie est journalisée avec sa source (Vente, Achat, Ajustement).

### 🚨 Gestion Proactive des Ruptures

- **Système d'Alerte Intelligent** : Surveillance automatisée des niveaux de stock.
- **Monitoring de Valeur** : La commande `stock:monitor-drop` analyse quotidiennement la valorisation globale du stock et alerte les administrateurs par e-mail en cas de chute suspecte (vol, perte massive).
- **Seuils Critiques** : Configuration par produit d'un seuil d'alerte déclenchant des notifications de réapprovisionnement.

### 📦 Infrastructure Optimisée

- **Docker Multi-Stage** : Images ultra-légères basées sur Alpine Linux. Séparation stricte entre l'environnement de build (compilateurs, nodejs) et l'environnement de production (runtime PHP pur).
- **Performance** : Utilisation de **Redis** pour le cache et les files d'attente, et **PostgreSQL** pour la fiabilité des données.

---

## Fonctionnalités Détaillées

### 1. Gestion Commerciale (Ventes)

- **Point de Vente (POS)** : Interface de saisie rapide.
- **Contrôle de Stock en Temps Réel** : Impossible de valider une vente si la quantité est insuffisante.
- **Calculs Automatiques** : Gestion des remises, totaux bruts/nets et génération de références uniques (`SALE-XXXX`).

### 2. Chaîne d'Approvisionnement (Achats)

- **Workflow de Validation** :
    1.  Création du bon de commande (Statut : _En attente_).
    2.  Validation et réception physique.
    3.  Incrémentation automatique du stock (Statut : _Reçu_).
- **Import Excel de Masse** : Module dédié pour importer des centaines de lignes d'achats fournisseurs, avec validation de l'existence des produits et fournisseurs avant import.

### 3. Reporting & Tableau de Bord

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

- **Installation Automatisée (Windows)** :
  Lancez simplement `install.bat` pour configurer l'environnement de production.
- **Développement** :
  Lancez `setup-dev.bat` pour un environnement avec Xdebug et montage de volumes en temps réel.

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

2.  **Lancer l'installation**
    - **Windows** : Double-cliquez sur `setup-dev.bat`.
    - **Linux/Mac** :
        ```bash
        cp .env.example .env
        docker compose -f compose.dev.yaml up -d --build
        docker compose -f compose.dev.yaml exec php-fpm composer install
        docker compose -f compose.dev.yaml exec php-fpm php artisan migrate:seed
        ```

3.  **Accéder à l'application**
    - URL : `http://localhost`
    - Mailpit (Emails locaux) : `http://localhost:8025` (si activé)

---

## Architecture Technique (Backend)

Le code suit les principes **SOLID** et une **Architecture Orientée Services (SOA)** pour une maintenabilité maximale.

### Couches Applicatives

1.  **Controllers** (`PurchaseController`, `SaleController`) : Reçoivent la requête HTTP, valident les entrées via des `FormRequest`, et délèguent le travail aux Services.
2.  **Services** (`SaleService`, `StockService`) : Contiennent toute la logique métier.
    - _Exemple_ : `SaleService::createSale` gère la transaction DB, vérifie le stock, crée la vente, décrémente le stock via `StockService` et génère les lignes de facture.
3.  **Models & Eloquent** : Représentation des données et relations (`Product`, `Sale`, `StockMovement`).

### Automatisation & Tâches de Fond (Cron)

Le système repose sur le scheduler Laravel pour les tâches critiques :

- **Monitoring Stock** (`stock:monitor-drop`) :
    ```bash
    # Exécuté toutes les heures ou quotidiennement
    php artisan stock:monitor-drop --threshold=15
    ```
    Vérifie si la valeur du stock a chuté de plus de 15% (paramétrable) et alerte le support.

Pour activer les tâches planifiées en production, une seule entrée Cron est nécessaire :

```cron
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Système de Sauvegarde

L'application est configurée pour supporter des sauvegardes régulières (Base de données + Fichiers) garantissant une reprise d'activité rapide en cas d'incident (Disaster Recovery).

---

## CI/CD (Intégration Continue)

Le projet intègre un pipeline GitHub Actions complet (`.github/workflows/ci.yml`) :

1.  **Linting** : Vérification automatique du style de code (Laravel Pint).
2.  **Testing** : Exécution des tests unitaires et fonctionnels (Pest/PHPUnit).
3.  **Build & Push** : Construction de l'image Docker de production et envoi sur le registre (GHCR) uniquement si les tests passent.

---

_Développé avec ❤️ pour une gestion de stock sans faille._
