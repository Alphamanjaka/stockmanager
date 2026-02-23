# Changelog

Ce fichier recense tous les changements notables apportés au projet "Vitrine".

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2023-10-27

### Ajouté

- **Architecture** : Mise en place d'une architecture orientée services (Service Layer) pour isoler la logique métier (PurchaseService, StockService, SupplierService).
- **Gestion des Produits** : CRUD complet avec gestion des prix, quantités et seuils d'alerte de stock.
- **Catégories** : Système de catégorisation hiérarchique (parent/enfant).
- **Fournisseurs** :
    - Gestion des fiches fournisseurs.
    - Statistiques détaillées (Montant total dépensé, Top 5 des produits achetés).
- **Achats & Stocks** :
    - Création de bons de commande.
    - Workflow de validation : le stock n'est incrémenté que lorsque le statut passe à "Reçu".
    - Calcul automatique des totaux et sous-totaux.
- **Import de Données** :
    - Module d'importation Excel pour les achats (`PurchaseImport`).
    - Regroupement automatique des lignes par `reference_groupe`.
    - Validation des données (existence fournisseur/produit) avant import.
- **Monitoring & Alertes** :
    - Commande Artisan `stock:monitor-drop` pour surveiller la valeur globale du stock.
    - Envoi automatique d'emails aux administrateurs (`back_office`) en cas de chute anormale de la valeur du stock (seuil configurable).
- **Tests** : Tests unitaires et fonctionnels pour la commande de monitoring (`MonitorStockDropTest`).
- **Seeders** : Jeux de données complets pour peupler la base (Produits, Fournisseurs, Achats, Ventes) via Faker.

### Sécurité

- Validation des entrées via des `FormRequest` dédiés.
- Gestion des transactions de base de données (`DB::transaction`) pour assurer l'intégrité des données lors des achats et mouvements de stock.
- Rôles utilisateurs (ex: `back_office`) pour restreindre l'accès aux fonctionnalités sensibles et aux alertes.

### Technique

- Intégration de `maatwebsite/excel` pour la gestion des fichiers Excel.
