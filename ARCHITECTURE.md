# Architecture : structure par modules

Le code métier est découpé en **modules** indépendants dans `app/Modules/`, autour d'un **socle** commun dans `app/Core/`.
Chaque module regroupe tout ce qui le concerne : modèles, services, contrôleurs, vues, routes, migrations et factories.

## Vue d'ensemble

```
app/
├── Core/                     Socle : authentification, utilisateurs, paramètres,
│                             tableaux de bord, recherche globale, import centralisé
├── Modules/
│   ├── Catalog/              Produits, variantes (options libres, marque, SKU), catégories, couleurs, marques
│   ├── Stock/                Mouvements de stock, alertes, valorisation
│   ├── Inventory/            Inventaires physiques (comptage, écarts, ajustement du stock)
│   ├── Purchase/             Achats fournisseurs, panier, réapprovisionnement depuis les ruptures
│   ├── Sale/                 Ventes (back office et vendeurs), client de la vente, factures PDF
│   ├── Contact/              Fournisseurs et clients
│   └── Backup/               Sauvegarde en arrière-plan : archive ZIP (Spatie), historique `backup_runs`, sans UI
│
├── Http/Controllers/Controller.php   Contrôleur de base Laravel
├── Http/Middleware/          Contrôle d'accès (back office / front office), langue
├── Models/User.php           Reste ici : son nom de classe est stocké en base (notifications, jetons)
└── Providers/                AppServiceProvider
resources/views/layouts/      Layouts partagés par tous les modules
routes/web.php                Points d'entrée publics (connexion, inscription, langue, accueil)
routes/console.php            Tâches planifiées
```

## Contenu d'un module

```
app/Modules/Purchase/
├── PurchaseServiceProvider.php   Déclare le module (nom court : 'purchase')
├── Models/                       Namespace App\Modules\Purchase\Models
├── Services/                     Logique métier
├── Http/Controllers/  Http/Requests/  Http/Resources/
├── Imports/                      Imports Excel/CSV du module
├── Console/Commands/             Commandes Artisan (déclarées dans $commands du provider)
├── Database/Migrations/          Migrations du module
├── Database/Factories/           Factories (liées au modèle par #[UseFactory])
├── Resources/views/              Vues : view('purchase::purchases.index')
└── Routes/
    ├── admin.php                 Back office  : /admin/..., noms admin.*
    ├── saler.php                 Front office : /saler/..., noms saler.*
    └── api.php                   AJAX du back office : /api/...
```

Le chargement est automatique grâce à `App\Core\Support\ModuleServiceProvider`, dont hérite chaque provider de module.
Tous les providers sont listés dans `bootstrap/providers.php`.

### Sécurité des routes

La protection est appliquée **par le nom du fichier**, dans `ModuleServiceProvider` :

| Fichier | Préfixe | Noms | Middlewares |
|---|---|---|---|
| `Routes/admin.php` | `/admin` | `admin.*` | `web`, `auth`, `ensure.back.office` |
| `Routes/saler.php` | `/saler` | `saler.*` | `web`, `auth`, `ensure.front.office` |
| `Routes/api.php` | `/api` | (libres) | `web`, `auth`, `ensure.back.office` |

Une route placée dans le bon fichier ne peut donc pas être exposée sans contrôle d'accès par oubli.
Les paramètres d'URL numériques partagés entre modules (ex. `{sale}` sous `/saler`) sont contraints à des chiffres
pour que l'ordre de chargement des modules n'ait pas d'importance.

## Règles de dépendance

| Module | Peut utiliser |
|---|---|
| Catalog | — |
| Contact | — |
| Stock | Catalog |
| Sale | Stock, Catalog, Contact (client de la vente) |
| Inventory | Stock, Catalog |
| Purchase | Stock, Catalog, Contact |
| Backup | — (module système : sauvegardes) |

- Un module n'utilise jamais un module situé plus bas que lui dans ce tableau (pas de dépendance circulaire).
- **Toute variation de stock est un mouvement**, créé par `StockService` (seul à écrire la colonne `stock`) :

  | Origine | Effet | Méthode |
  |---|---|---|
  | Achat passé au statut « Reçu » | entrée | `addStock` |
  | Vente | sortie | `removeStock` |
  | Inventaire physique validé | écart compté | `adjustTo` |
  | Correction manuelle (secours en cas d'erreur, motif obligatoire) | entrée ou sortie | `correct` |

- **Le catalogue ne gère pas le stock** : il crée les produits et leurs variantes, qui démarrent à 0.
  Le stock y est en lecture seule (formulaires, fiche produit, import des produits : aucune saisie de stock).
  Le modèle `ProductColor` le garantit : `stock` n'est pas assignable et toute modification par le modèle lève
  une `LogicException`. Dans les tests, une variante avec un stock de départ se crée avec `ProductColor::forceCreate()`.
- Tous les modules peuvent utiliser le socle (`App\Core`) : `BaseService`, `BaseResourceController`, `settings()`, `User`.
- Les fonctions **transverses du Core** (tableaux de bord, recherche globale, import centralisé) lisent les modules ;
  un module ne doit jamais dépendre de ces fonctions.

### Exceptions connues (lecture seule, à résorber)

| Dépendance | Où | Raison |
|---|---|---|
| Catalog → Sale | `ProductColorController@index` | La liste des produits affiche les produits les plus / moins vendus |
| Contact → Purchase | `SupplierController@show`, `SupplierService`, `Supplier::purchases()` | La fiche fournisseur affiche son historique d'achats |

## Recettes

**Créer une migration dans un module**

```bash
php artisan make:migration add_xxx_to_purchases_table --path=app/Modules/Purchase/Database/Migrations
```

Laravel ne mémorise que le nom des fichiers de migration : les migrations existantes ont été déplacées dans les modules
sans être rejouées.

**Ajouter une page au back office d'un module**

1. Le contrôleur dans `Http/Controllers/`, la vue dans `Resources/views/`.
2. La route dans `Routes/admin.php` (sans préfixe ni middleware : ils sont ajoutés automatiquement).
3. Dans le contrôleur : `return view('<module>::dossier.vue', [...])`. Les vues étendent `layouts.app-back-office`.
4. Pour qu'elle apparaisse dans le menu : une entrée dans la propriété `$menu` du provider du module (voir ci-dessous).

**Menu du back office (lanceur de modules)**

Le layout n'écrit aucun lien en dur : il affiche le menu déclaré par les modules, résolu par
`App\Core\Navigation\BackOfficeMenu` pour la page courante.

- **Espaces** : les tuiles du lanceur (bouton en haut à gauche, ou Alt + L). Le Core les déclare dans
  `CoreServiceProvider::$menuSpaces` (Tableau de bord, Catalogue, Stock, Achats, Ventes, Administration).
- **Entrées** : chaque module range ses pages dans un espace via `$menu`. Une entrée peut avoir des `children`
  (menu secondaire, affiché en liste déroulante). Un module peut remplir l'espace d'un autre : Contact place
  les fournisseurs dans Achats et les clients dans Ventes.

```php
protected array $menu = [
    'catalog' => [
        ['label' => 'backoffice.products', 'icon' => 'bi-box', 'route' => 'admin.products.index', 'shortcut' => 'p',
         'children' => [
             ['label' => 'backoffice.menu.all_products', 'route' => 'admin.products.index'],
             ['label' => 'backoffice.menu.new_product', 'route' => 'admin.products.create'],
         ]],
    ],
];
```

Une entrée dont la route n'existe pas est masquée (le layout ne plante pas), un espace vide disparaît.
L'entrée active, le fil d'Ariane et les raccourcis Alt + touche en sont déduits. `BackOfficeMenuTest` vérifie que
chaque raccourci est unique et que chaque libellé est traduit en en/fr/es (`lang/*/backoffice.php`).

**Interface : thème et composants**

- **Charte** : toutes les couleurs, tailles et formes sont des jetons CSS dans `resources/css/theme/tokens.css`
  (thème clair et sombre). Bootstrap y est branché par ses variables ; on ne code pas de couleur en dur dans une vue.
- **Icônes** : Bootstrap Icons uniquement (`<i class="bi bi-box">`, menu : `'icon' => 'bi-box'`).
- **Composants Blade** (`resources/views/components/ui/`) : `<x-ui.card>` (titre, actions, barre d'outils, pied,
  pagination), `<x-ui.filters>` + `<x-ui.search>` + `<x-ui.status-filter>` (recherche d'une liste, actifs / archives),
  `<x-ui.field>` (libellé, contrôle, aide, erreur), `<x-ui.stat>` (indicateur), `<x-ui.badge>` / `<x-ui.status>`,
  `<x-ui.empty-row>`, `<x-ui.delete-button>`, `<x-ui.alert>`, `<x-ui.errors>`, `<x-ui.archived-notice>` ;
  menus : `<x-ui.actions-menu>` + `<x-ui.menu-item>` + `<x-ui.export-items>`.
- **Mise en page d'une page** : titre dans `@section('title')` (affiché en en-tête avec le fil d'Ariane), sous-titre
  facultatif dans `@section('subtitle')`, dans `@section('actions')` : l'action principale en `btn-primary` (une seule),
  les autres dans le menu engrenage `<x-ui.actions-menu>` (exports, import, actions secondaires) plutôt qu'en boutons.
  Listes : une carte (filtres en barre d'outils, tableau, pagination en pied) ; actions de ligne
  dans `<x-ui.actions-menu row>` (« ⋮ »), le nom de l'élément menant à sa fiche. Formulaires : Annuler puis Enregistrer,
  à droite.
- **Montants** : `money($montant)` (devise et position des Paramètres).
- **Confirmation** : `data-confirm="Question ?"` sur un bouton d'envoi ou un formulaire (`resources/js/ui.js`) ;
  `data-confirm-tone="danger"` pour une suppression.

**Archiver plutôt que supprimer**

Les données de référence (produits, catégories, marques, couleurs, fournisseurs, clients, utilisateurs) s'archivent :
trait `App\Core\Models\Concerns\Archivable` (colonne `archived_at`, scopes `active()`, `archived()`, `withStatus()`).

- Un élément archivé disparaît des listes (filtre « Archived » pour le retrouver) et des listes de choix (caisse,
  bon de commande, formulaires) ; ses ventes, achats, mouvements et inventaires restent complets. Un compte archivé
  ne peut plus se connecter (`LogoutArchivedUser`).
- Pas de scope global : une relation vers un élément archivé (ligne de facture → produit) le renvoie toujours.
  **Une liste de choix filtre donc explicitement avec `->active()`** (`BaseService::getAll()` le fait pour un modèle
  archivable) ; garder l'élément déjà choisi sur un document modifié (`SupplierService::getActiveSuppliers($keepId)`).
- `archiveBlocker()` refuse un archivage (catégorie qui contient des produits actifs, son propre compte).
- Contrôleur : trait `ArchivesRecords` (actions `archive` / `restore`, un élément ou une sélection `ids[]`,
  `deletePermanently()`), routes `<ressource>.archive` / `<ressource>.restore` déclarées avant la ressource.
- **Suppression définitive** : seulement depuis les archives, et la base la refuse pour un élément utilisé : les clés
  étrangères d'historique sont en `ON DELETE RESTRICT` (migrations `2026_10_18_*`). Plus aucune cascade n'efface
  de mouvement, de ligne d'achat ou d'inventaire.

**Exports PDF et Excel des listes**

Chaque liste a un export dans son module (`Exports/`), qui étend `App\Core\Exports\ListExport` et ne décrit que
son titre, ses colonnes (`Column::make('Price', 'price')->money()->total()`) et sa requête — idéalement la même que
la page, pour exporter exactement ce qui est affiché. La base fournit : mêmes filtres que la page, export de la
sélection (`ids[]`), en-tête de l'entreprise et pied numéroté (`layouts.pdf`), filtres appliqués, totaux, et la
version Excel (valeurs brutes, en-tête figé, filtres automatiques). Le PDF est limité à 1 000 lignes (l'Excel est
complet). Déclaration dans le provider : `protected array $exports = ['products' => ProductsExport::class];` ;
URL : `admin/exports/{clé}/{pdf|xlsx}` ; menu : `<x-ui.export-items list="products" />`.

**Créer un nouveau module**

1. `app/Modules/<Nom>/<Nom>ServiceProvider.php` qui étend `ModuleServiceProvider` avec `protected string $name = '<nom>';`.
2. L'ajouter dans `bootstrap/providers.php`.
3. Créer les dossiers nécessaires (seuls ceux qui existent sont chargés).

**Factories d'un modèle de module**

```php
#[UseFactory(PurchaseFactory::class)]
class Purchase extends Model { use HasFactory; }

class PurchaseFactory extends Factory { protected $model = Purchase::class; }
```

## Variantes flexibles

Les variantes ne dépendent plus d'une couleur obligatoire : options libres en JSONB, marque et SKU généré.
Conception, procédure de migration en production et tests sur PostgreSQL : voir [docs/migration-variantes.md](docs/migration-variantes.md).

## Tests

Les tests sont rangés par module dans `tests/Feature/<Module>/`.
`tests/Feature/Http/` contient les tests transverses : accès à l'API, inscription, et `PagesRenderTest`,
qui ouvre chaque page avec des données réelles et échoue si une vue ou une classe est introuvable.
