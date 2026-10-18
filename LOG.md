# Journal de Bord - Vitrine (StockManager)

## [16-10-2026] - Achats : le panier devient un bon de commande

### 🧾 Saisie d'un achat

- **Problématique** : la création d'un achat ressemblait à un panier e-commerce (une ligne = un rechargement de page,
  fournisseur choisi à la fin, lignes non modifiables, pas de remise ni de référence fournisseur), et le coût proposé
  était le **prix de vente** de la variante. Le message final annonçait à tort que le stock était mis à jour.
- **Solution** :
    - Un seul formulaire pour la création et la modification d'un brouillon (`purchases/_form.blade.php`,
      `resources/js/purchase-form.js`) : fournisseur en premier, référence fournisseur, notes, lignes modifiables,
      remise, totaux en direct.
    - Recherche au clavier (F2, flèches, Entrée) ; un produit déjà présent est mis en évidence au lieu d'être doublé.
    - Coût proposé : dernier prix payé à ce fournisseur (`admin.purchases.supplier-prices`), sinon le prix d'achat
      de la variante. Bouton « Ajouter les ruptures » de ce fournisseur (quantités suggérées).
    - Trois actions : brouillon, commandé, ou réception immédiate (entrée en stock, avec confirmation), en une seule
      transaction (`PurchaseService::savePurchaseOrder`). Fiche achat : boutons d'étape au lieu d'une liste de statuts.
    - Validation commune (`SavePurchaseRequest`) : fournisseur obligatoire, une variante par ligne, remise ≤ total.
    - Migration : colonnes `supplier_reference` et `notes` sur `purchases`. Le panier en session est supprimé.

## [16-10-2026] - Stock en lecture seule : tout passe par un mouvement

### 📦 Règle

- **Problématique** : le catalogue pouvait fixer le stock (stock initial à la création d'un produit ou d'une variante,
  modification du stock depuis la fiche produit, colonne `stock` de l'import des produits). Même historisé,
  ce stock « déclaré » ne correspondait à aucune opération réelle.
- **Solution** :
    - Le stock n'évolue que par des mouvements : achat reçu (entrée), vente (sortie), inventaire physique validé,
      et correction manuelle en secours d'une erreur de saisie.
    - Catalogue : création de produits seulement ; variantes à 0 ; stock affiché en lecture seule.
      L'événement `VariantStockDeclared` et son écouteur sont supprimés.
    - Modèle `ProductColor` : `stock` non assignable, modification par le modèle refusée (`LogicException`) ;
      `StockService` est le seul à écrire la colonne, en même temps que le mouvement.
    - « Nouveau mouvement » devient « Correction de stock » : motif obligatoire, enregistré « Correction : … »,
      présélection de la variante depuis la fiche produit.
    - Seeder : le stock de démonstration vient d'achats reçus.

## [16-10-2026] - Thème « entreprise » : charte, composants et vues uniformes

### 🎨 Charte graphique et design system

- **Problématique** : Couleurs, cartes, boutons, tableaux et formulaires différents d'une page à l'autre ;
  deux bibliothèques d'icônes (Font Awesome et Bootstrap Icons) ; mode sombre partiel ; montants affichés en
  « MGA », « Mga » ou « € » selon la page.
- **Solution** :
    - Jetons CSS (`resources/css/theme/tokens.css`) : bleu marine de marque, gris neutres, couleurs d'état ;
      thème sombre complet (Bootstrap suit via `data-bs-theme`).
    - Habillage Bootstrap, Select2, Tabulator et SweetAlert2 aligné sur ces jetons (`theme/components.css`, `theme/vendors.css`).
    - Bootstrap Icons seul (Font Awesome n'est plus chargé), menu des modules compris.
    - Composants Blade `x-ui.*` (cartes, filtres, champs, indicateurs, badges, états vides, suppression).
    - Helper `money()` pour tous les montants ; `SettingService` partagé par requête (paramètres lus une fois).

### 🧭 Layouts

- Barre d'application marine (lanceur, module, recherche, notifications, profil), menu du module en deuxième ligne,
  en-tête de page (fil d'Ariane, titre, actions).
- Front office et page de connexion sur la même charte (plus de dégradé violet ni de CDN).
- Confirmations (`data-confirm`) et messages de session en boîtes de dialogue de la charte.

### 🐛 Corrections au passage

- Création de catégorie : le parent choisi était ignoré (`parents_id` au lieu de `parent_id`).
- Liste des achats : le bouton « Reçu » n'avait pas d'URL (`urls.update_state` absent de l'API).
- Liste des utilisateurs : la recherche et le filtre par rôle sont maintenant appliqués.
- Front office : l'onglet actif de la navigation est de nouveau mis en évidence.

## [11-06-2026] - Optimisation de l'Espace UI et Amélioration de la Gestion des Variantes

### 📊 Refonte des KPI (Barre d'État Compacte)

- **Problématique** : Les cartes de KPI (Indicateurs clés) occupaient trop d'espace vertical sur le Dashboard, la gestion des catégories et les ventes, masquant le contenu principal.
- **Solution** : Transformation des blocs massifs en une "Barre d'État" (Status Bar) horizontale ultra-compacte.
    - Regroupement des métriques dans un bandeau léger.
    - Remplacement des icônes larges par des icônes inline colorées.
    - Uniformisation du design sur l'ensemble du Back-Office (Dashboard, Catégories, Ventes, Achats) et de l'interface Vendeur.
- **Résultat** : Gain d'espace vertical de plus de 50% et une lecture immédiate des performances sans scroller.

### 💎 Modernisation des Modaux de Variantes

- **Problématique** : Les modaux de création/édition des variantes étaient trop rudimentaires et il manquait le champ "Prix d'achat" pour un suivi précis de la rentabilité.
- **Solution** :
    - Refonte complète de l'UI des modaux avec des groupes d'entrées (Input Groups) et des icônes FontAwesome.
    - **Intégration du Prix d'Achat** : Ajout du champ `price_purchase` dans les formulaires, la validation du contrôleur et l'affichage de la table des variantes.
    - Amélioration de la hiérarchie visuelle (labels en majuscules, boutons arrondis "pill", ombres portées).
- **Résultat** : Une saisie de données plus intuitive et professionnelle, permettant désormais de calculer les marges à l'avenir.

### 🔧 Cohérence Globale

- **Action** : Synchronisation du style visuel des KPI entre le Dashboard, la liste des catégories et les historiques de ventes (Back & Front Office).

---

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
