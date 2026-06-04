# Journal de Bord - Vitrine (StockManager)

## [04-06-2026] - Refonte UI & Système de Recherche Globale

### 🔍 Système de Recherche (Omnisearch)

- **Problématique** : La recherche était limitée à des entités spécifiques sans vue d'ensemble.
- **Solution** : Création d'une fonctionnalité de recherche globale hybride.
    - **Backend** : Implémentation du `GlobalSearchService` orchestrant les recherches dans `ProductColor`, `Purchase`, `Sale` et `StockMovement`.
    - **Frontend** : Création de `global-search.js` avec gestion du _debouncing_ (300ms) et recherche instantanée dans les routes statiques.
- **Résultat** : Un seul champ de recherche permet désormais de trouver des pages (ex: "Liste de produits") ou des données précises (ex: "Vente #REF").

### 🚀 Barre Latérale (Sidebar)

- **Bugs corrigés** :
    - L'affichage/masquage ne persistait pas.
    - Conflit de logique entre `back.js` et `sidebar.js`.
    - Erreur de texte parasite ("si") dans le bouton toggle.
- **Améliorations** :
    - **Persistance** : Utilisation du `localStorage` pour mémoriser l'état (ouvert/fermé) entre les pages.
    - **Mode Réduit (Collapsed)** : Les textes se masquent pour ne laisser que les icônes.
    - **Sous-menus Horizontaux** : En mode réduit, les sous-menus s'affichent désormais horizontalement au survol/clic sous forme de popups d'icônes.
    - **Tooltips** : Ajout d'attributs `title` pour afficher le nom des menus au survol de l'icône quand la barre est fermée.
- **Résultat** : Une navigation plus fluide et un gain d'espace de travail sur les petits écrans.

### 🎨 Gestion des Couleurs

- **Bug corrigé** : La recherche locale dans `colors/index.blade.php` ne fonctionnait plus à cause d'une faute de frappe (`consol.log`) et d'un mauvais placement de section (`@section` au lieu de `@push`).
- **Correction** : Nettoyage du script JS et sécurisation du filtrage des lignes (ignore les lignes de "résultats vides").
- **Résultat** : Filtrage instantané des couleurs fonctionnel.

## [04-06-2026] - Optimisation de l'Ergonomie et de la Gestion des Achats

### 🛒 Interface de Commande Interactive
- **Section** : Feature/Refacto
- **Problématique** : La sélection de produits par menus déroulants (`select2`) devenait fastidieuse avec un catalogue croissant et manquait de réactivité.
- **Solution** : Remplacement des selects par un système d'autocomplétion asynchrone et refonte complète des vues `create` et `edit` des achats en deux colonnes (Recherche/Saisie à gauche, Panier dynamique à droite).
- **Résultat** : Une expérience utilisateur fluide et une saisie de commande considérablement accélérée.

### 📦 Gestion des Variantes One-Page
- **Section** : Feature
- **Problématique** : La modification des informations de variantes (prix, stock, alertes) nécessitait trop de navigations entre différentes pages.
- **Solution** : Intégration de modaux de gestion CRUD (Ajout/Édition/Suppression) directement dans la vue `products.show`.
- **Résultat** : Centralisation complète de la gestion d'un produit et de ses déclinaisons sur une seule vue.

### 🛠️ Nettoyage du Workflow d'Achat
- **Section** : Refacto
- **Problématique** : Le statut "Paid" surchargeait inutilement le suivi logistique des achats dans cette application de gestion de stock.
- **Solution** : Suppression du statut "Paid" dans la base de données, les services et l'interface pour se concentrer sur le cycle Draft -> Ordered -> Received.
- **Résultat** : Un cycle de vie de commande plus simple, plus lisible et aligné sur les besoins métiers.

### 🐛 Corrections de Stabilité et de Syntaxe
- **Section** : Bug
- **Problématique** : Présence d'erreurs de routes indéfinies (`admin.purchases.cart.add`) et de `ParseError` dans Blade lors de l'injection de données JSON complexes multi-lignes.
- **Solution** : Correction de la définition des routes et déportation de la logique de formatage des données JSON depuis les fichiers Blade vers le `PurchaseController`.
- **Résultat** : Élimination des erreurs d'affichage et meilleure séparation des responsabilités (MVC).

### 📝 Documentation

- **README.md** : Ajout d'une section sur l'architecture de recherche et un guide technique pour étendre le système à de nouvelles entités.
- **Fichiers de suivi** : Mise à jour du `CHANGELOG.md` et création de ce fichier `LOG.md`.

---

## Structure du LOG

Chaque entrée doit suivre ce format :

1. **Titre** : [Date] - Résumé court.
2. **Section** : (Bug/Feature/Refacto).
3. **Problématique** : Pourquoi le changement était nécessaire.
4. **Solution** : Ce qui a été codé.
5. **Résultat** : L'impact sur l'utilisateur final.
