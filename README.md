# Projet Vitrine - Gestion de Stock

**Vitrine** est une application web développée avec le framework Laravel, conçue pour la gestion complète des produits, des stocks, des fournisseurs et des achats. Elle offre une interface pour suivre les mouvements de stock, automatiser les alertes et faciliter la gestion commerciale.

## Table des matières

1.  Fonctionnalités principales
2.  Architecture technique
3.  Modèle de données (Base de données)
4.  Installation et configuration
5.  Système de sécurité
6.  Frontend
7.  Tâches planifiées (Cron)
8.  Imports de données
9.  Dépannage

## Fonctionnalités principales

- **Gestion des Produits** : CRUD complet pour les produits, incluant le nom, la description, le prix de vente, la quantité en stock et un seuil d'alerte.
- **Catégorisation** : Organisation des produits en catégories hiérarchiques (parent/enfant).
- **Gestion des Fournisseurs** : Suivi des informations relatives aux fournisseurs.
- **Gestion des Achats** :
    - Création de bons de commande associés à un fournisseur.
    - Processus de statut d'achat (ex: "En attente", "Reçu").
    - Mise à jour automatique des stocks uniquement lorsque l'achat est marqué comme "Reçu".
- **Import de Données** : Possibilité d'importer des achats en masse depuis un fichier Excel, avec validation des données.
- **Surveillance Automatisée** : Une commande `artisan` surveille la valeur totale du stock et envoie une alerte par e-mail aux administrateurs en cas de chute brutale.
- **Reporting** : Statistiques sur les achats (dépenses totales, valeur moyenne, etc.).

## Architecture technique

Le projet est bâti sur une architecture robuste et modulaire.

- **Framework** : PHP 8.x / Laravel 10.x.
- **Architecture Applicative** : L'application suit un modèle **MVC (Modèle-Vue-Contrôleur)** enrichi par une **Architecture Orientée Services**. La logique métier complexe est isolée dans des classes de service (ex: `PurchaseService`, `StockService`).
    - **Contrôleurs** : Gèrent les requêtes HTTP et font le lien avec les services.
    - **Services** : Centralisent la logique métier (ex: `processPurchase` dans `PurchaseService`). Cela garantit que les mêmes règles sont appliquées, que l'action provienne d'un contrôleur web, d'un import ou d'un seeder.
    - **Modèles (Eloquent)** : Interagissent avec la base de données.
- **Dépendances Notables** :
    - `maatwebsite/excel` : Pour l'import et l'export de fichiers Excel.
    - `fakerphp/faker` : Pour la génération de données de test (seeders).

## Modèle de données (Base de données)

La structure de la base de données est gérée via les migrations Laravel. Les tables principales sont :

- `users` : Gère les utilisateurs et leurs rôles (ex: `back_office` pour les administrateurs).
- `products` : Contient les informations des produits (`name`, `price`, `quantity_stock`, `alert_stock`, `category_id`).
- `categories` : Stocke les catégories de produits avec une relation `parent_id` pour la hiérarchie.
- `suppliers` : Répertoire des fournisseurs.
- `purchases` : Enregistre les en-têtes des achats (`reference`, `supplier_id`, `total_amount`, `state`).
- `purchase_items` : Lignes de détail pour chaque achat (`product_id`, `quantity`, `unit_price`).
- `stock_movements` (supposé) : Table pour tracer chaque entrée et sortie de stock pour une meilleure auditabilité, utilisée par le `StockService`.

## Installation et configuration

Pour déployer le projet en local, suivez ces étapes :

1.  **Cloner le dépôt**

    ```bash
    git clone [URL_DU_DEPOT]
    cd vitrine
    ```

2.  **Installer les dépendances**

    ```bash
    composer install
    npm install
    ```

3.  **Configurer l'environnement**

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

    Modifiez le fichier `.env` pour configurer la base de données (`DB_*`) et le serveur de messagerie (`MAIL_*`).

4.  **Base de données et données de test**

    ```bash
    php artisan migrate --seed
    ```

    Cette commande exécute les migrations et les seeders, peuplant la base de données avec des données de démonstration (produits, fournisseurs, achats...).

5.  **Compiler les assets frontend**

    ```bash
    npm run dev
    ```

6.  **Lancer le serveur**
    ```bash
    php artisan serve
    ```
    L'application sera accessible à l'adresse `http://127.0.0.1:8000`.

## Système de sécurité

La sécurité est un aspect central de l'application, gérée via plusieurs mécanismes Laravel.

- **Authentification** : Gérée par le système d'authentification intégré de Laravel.
- **Autorisation** :
    - Les `FormRequest` (ex: `UpdateCategoryRequest`) contiennent une méthode `authorize()` pour vérifier les permissions, bien qu'actuellement permissive (`return true;`).
    - Un système de rôles est en place (ex: le rôle `back_office` est utilisé pour l'envoi d'alertes), suggérant une logique de contrôle d'accès basée sur les rôles (RBAC).
- **Validation des données** :
    - Les requêtes entrantes sont validées par des classes `FormRequest` dédiées (ex: `StoreProductRequest`). Cela protège contre les données invalides avant même d'atteindre la logique métier.
    - L'import Excel utilise également un système de validation (`WithValidation`) pour garantir l'intégrité des données importées.
- **Protections standards** :
    - **CSRF** : Protection automatique sur toutes les routes web via le middleware `VerifyCsrfToken`.
    - **Injection SQL** : Prévenue par l'utilisation de l'ORM Eloquent qui utilise des requêtes préparées.

## Frontend

Les fichiers fournis concernent principalement le backend. Le frontend est probablement construit avec :

- **Laravel Blade** : Le moteur de template natif de Laravel pour construire les vues HTML.
- **Tailwind CSS / Bootstrap** : (Hypothèse) Un framework CSS pour le style des composants.
- **JavaScript / Alpine.js / Vue.js** : Pour l'interactivité côté client.

Les vues Blade sont utilisées pour afficher les formulaires, les listes de données (produits, achats) et les tableaux de bord. Les e-mails transactionnels (comme `StockDropAlert`) sont également rendus via des vues Blade.

## Tâches planifiées (Cron)

Une tâche planifiée est définie pour surveiller la valeur du stock.

- **Commande** : `php artisan stock:monitor-drop --threshold=15`
- **Description** : Calcule la variation de la valeur totale du stock par rapport à la veille. Si la chute dépasse le seuil (`threshold`), une alerte est envoyée.
- **Configuration** : Pour l'activer en production, ajoutez la ligne suivante à votre crontab sur le serveur :

    ```cron
    * * * * * cd /chemin/vers/votre/projet && php artisan schedule:run >> /dev/null 2>&1
    ```

    La fréquence exacte peut être ajustée dans `app/Console/Kernel.php`.

## Imports de données

Le système permet d'importer des achats groupés via un fichier Excel.

- **Logique** : La classe `App\Imports\PurchaseImport` gère le traitement du fichier.
- **Format attendu** : Le fichier doit contenir les colonnes suivantes :
    - `reference_groupe` : Un identifiant unique pour regrouper plusieurs lignes en un seul achat.
    - `email_fournisseur` : L'email d'un fournisseur existant.
    - `nom_produit` : Le nom d'un produit existant.
    - `quantite` : La quantité achetée.
    - `cout_unitaire` : Le prix d'achat unitaire du produit.
- **Processus** : L'import valide chaque ligne, regroupe les articles par `reference_groupe`, puis utilise le `PurchaseService` pour créer l'achat, garantissant ainsi que toutes les règles métier sont respectées.

## Dépannage

Voici quelques solutions aux problèmes courants rencontrés lors de l'utilisation ou du développement de l'application.

### 1. Erreurs lors de l'import Excel
*   **Symptôme** : L'import échoue ou ne crée aucune donnée.
*   **Solution** :
    *   Vérifiez que les en-têtes de colonnes correspondent exactement à : `reference_groupe`, `email_fournisseur`, `nom_produit`, `quantite`, `cout_unitaire`.
    *   Assurez-vous que le fournisseur (`email_fournisseur`) et le produit (`nom_produit`) existent déjà dans la base de données.
    *   Vérifiez les logs Laravel (`storage/logs/laravel.log`) pour des détails précis sur l'erreur.

### 2. Les emails d'alerte ne partent pas
*   **Symptôme** : La commande `stock:monitor-drop` indique un envoi, mais rien n'est reçu.
*   **Solution** :
    *   Vérifiez la configuration `MAIL_*` dans votre fichier `.env`.
    *   Pour le développement local, utilisez `MAIL_MAILER=log` pour voir les emails dans `storage/logs/laravel.log` ou un outil comme Mailpit/Mailhog.

### 3. Problèmes de styles / Assets manquants
*   **Symptôme** : La page s'affiche sans CSS ou JavaScript.
*   **Solution** :
    *   Assurez-vous d'avoir lancé `npm install` et `npm run dev` (pour le développement) ou `npm run build` (pour la production).
