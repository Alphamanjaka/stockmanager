# Migration vers les variantes flexibles

Objectif : un produit peut varier selon **n'importe quelles options** (contenance, couleur, taille…),
avec une **marque** et un **SKU généré automatiquement**, sans casser la production.

| Exemple | Options du produit | Variantes |
|---|---|---|
| Gel déshydratant | Contenance : 25 ml, 30 ml | `GEL-DESHYDRATANT-25ML`, `GEL-DESHYDRATANT-30ML` |
| Postiche lisse | Couleur : Noir, Brun · Taille : 30 cm, 40 cm | `POSTICHE-LISSE-NOIR-30CM`, … (+ marque si renseignée) |
| Produit sans déclinaison | aucune | une variante par défaut |

## Modèle de données

```
categories.option_template  jsonb   options proposées pour la catégorie (Postiches → Couleur, Taille)
products.options            jsonb   [{"name":"Contenance","values":["25 ml","30 ml"]}]  (ordre = ordre d'affichage)
products.specs              jsonb   infos libres : {"Composition":"...","Origine":"Chine"}
brands                      table   référentiel des marques
product_colors (variante)   ─ option_values jsonb {"Contenance":"25 ml"}
                            ─ signature        "contenance=25 ml" (+ "|brand=ID") : détection des doublons
                            ─ sku              unique, généré
                            ─ brand_id         marque de la variante
                            ─ color_id         conservé pendant la transition
```

La table `product_colors` et la colonne `product_color_id` (ventes, achats, mouvements, inventaires) **ne changent pas** :
tout l'historique reste valide.

> PostgreSQL (`jsonb`) ne conserve pas l'ordre des clés. L'ordre d'affichage des valeurs (libellés, SKU) vient donc de
> `products.options`, puis de l'ordre alphabétique des noms d'options. Ne jamais se fier à l'ordre du JSON stocké.

## Les 4 étapes

| Étape | Contenu | Visible | Réversible |
|---|---|---|---|
| 1. Étendre | Migrations `2026_10_11_200000` et `200100` : uniquement des ajouts (colonnes facultatives, table `brands`, index) | Non | Oui (`migrate:rollback --step=2`) |
| 2. Remplir | Commande `catalog:migrate-variants` | Non | Oui (rien n'est supprimé, `color_id` intact) |
| 3. Basculer | Écrans produit par options (grille de variantes), marques, modèles d'options par catégorie, libellé partout, import avec options | Oui | Revenir à la version précédente |
| 4. Nettoyer | `color_id` facultatif, `sku` et `signature` obligatoires, unicité `(product_id, signature)`, suppression de l'ancienne colonne `attributes` | Non | **Non** : à faire quand tout est validé |

Pendant la transition, le modèle `ProductColor` complète automatiquement `option_values`, `signature` et `sku`
à chaque enregistrement : les écrans actuels (basés sur la couleur) produisent déjà des variantes complètes.

## Procédure de production (étapes 1 et 2)

### 0. Répétition (obligatoire)

1. Lancer une sauvegarde et la télécharger (Paramètres › Sauvegardes), ou :
   ```bash
   docker compose -f compose.prod.yaml exec php-fpm php artisan app:run-backup --now
   ```
2. La restaurer dans une base dédiée du PostgreSQL de Laragon (jamais dans `app`) :
   ```bash
   # le zip de sauvegarde contient db-dumps/postgresql-<base>.sql
   psql -h 127.0.0.1 -U alpha -d postgres -c "CREATE DATABASE rehearsal"
   psql -h 127.0.0.1 -U alpha -d rehearsal -f postgresql-app.sql
   ```
3. Mettre temporairement `DB_DATABASE=rehearsal` dans le `.env`, jouer les étapes 1 et 2 ci-dessous, vérifier le
   rapport, puis remettre `DB_DATABASE=app`.

   (Laragon fournit PostgreSQL 18, la production PostgreSQL 16 : une sauvegarde de la production se restaure sans
   problème dans la version plus récente.)

### 1. Déployer (étape 1)

Le conteneur `php-fpm` exécute `php artisan migrate --force` au démarrage : les migrations de l'étape 1
s'appliquent au déploiement. Elles n'ajoutent que des colonnes facultatives et des index (quelques millisecondes).

> Ne jamais déployer ce code sans ses migrations : le modèle écrit dans les nouvelles colonnes.
> Le démarrage du conteneur s'en charge ; en manuel, toujours lancer `php artisan migrate` d'abord.

### 2. Remplir les données (étape 2)

```bash
# Rapport seul : rien n'est écrit
docker compose -f compose.prod.yaml exec php-fpm php artisan catalog:migrate-variants --dry-run

# Exécution (relançable sans risque : seules les variantes incomplètes sont traitées)
docker compose -f compose.prod.yaml exec php-fpm php artisan catalog:migrate-variants
```

Règles appliquées :
- couleur « Rouge » → `{"Couleur": "Rouge"}` ;
- couleur « No Variant » ou « Sans variante » → variante par défaut, sans option ;
- options du produit reconstituées à partir de ses variantes ;
- SKU généré (`-2`, `-3`… en cas de collision) ; `color_id`, stock, prix et dates de modification inchangés.

### Vérifications

```sql
-- Toutes les variantes sont complètes (attendu : 0)
SELECT count(*) FROM product_colors WHERE option_values IS NULL OR sku IS NULL OR signature IS NULL;

-- Doublons à fusionner avant l'étape 4 (attendu à terme : aucune ligne)
SELECT product_id, signature, array_agg(id) FROM product_colors GROUP BY product_id, signature HAVING count(*) > 1;
```

### Doublons

Un doublon = deux variantes d'un même produit avec la même combinaison (ex. deux variantes « Blond » de la même
postiche, possible jusqu'ici faute de contrainte). Ils n'empêchent ni l'étape 2 ni l'étape 3, mais doivent être
fusionnés avant l'étape 4 : reporter le stock sur une des deux variantes (mouvement de stock), puis supprimer l'autre
si elle n'a jamais été vendue, ou la désactiver.

### Retour arrière

- Étape 2 : rien à annuler pour l'ancien code, qui ignore les nouvelles colonnes.
- Étape 1 : `php artisan migrate:rollback --step=2` supprime les nouvelles colonnes et la table `brands`
  (à n'utiliser qu'avec la version précédente du code).

## Étape 3 : ce qui change pour les utilisateurs

- **Catalogue › Marques** : liste, création, renommage ; une marque utilisée par des variantes ne peut pas être supprimée.
- **Catégories** : section « Product options for this category » (ex. Postiches → Couleur, Taille), proposée à la
  création des produits de la catégorie.
- **Nouveau produit** : options (axes et valeurs) pré-remplies depuis la catégorie, grille des combinaisons générée
  automatiquement (décocher celles qui n'existent pas ; marque, prix, coût, stock initial, seuil par variante ;
  « Apply to checked » pour remplir en masse), spécifications libres. Sans option : une seule variante.
- **Fiche produit** : options et spécifications modifiables (une valeur utilisée par une variante ne peut pas être
  retirée) ; ajout/modification de variante par ses valeurs d'options et sa marque ; une valeur nouvelle
  (ex. « 35 ml ») est ajoutée aux options ; le SKU ne change jamais.
- **Partout** (ventes, factures, achats, mouvements, inventaires, recherche) : libellé complet
  « Gel déshydratant — 30 ml », « Postiche lisse — Noir / 30 cm — Bella ».

### Format d'import des produits (une ligne = une variante)

| Colonne | Rôle |
|---|---|
| `name` | Produit (obligatoire) ; rapproché sans tenir compte de la casse |
| `category` | Catégorie (créée si besoin) |
| `brand` | Marque de la variante (créée si besoin) |
| `option_<nom>` | Une colonne par option : `option_contenance`, `option_couleur`, `option_taille`… |
| `color` | Ancien format, lu comme l'option « Couleur » (« No Variant » = pas d'option) |
| `price` | Prix de vente (obligatoire) |
| `price_purchase`, `alert_stock` | Facultatifs |
| `stock` | Stock à atteindre ; cellule vide = stock inchangé ; l'écart est historisé |
| `sku` | Facultatif : désigne une variante existante à mettre à jour |

Une ligne met à jour la variante existante (même SKU, sinon même produit + même combinaison + même marque), sinon la
crée : réimporter un fichier ne crée pas de doublon. Une combinaison incomplète arrête l'import (numéro de ligne
indiqué) et annule tout. L'import des achats accepte aussi une colonne `sku`, à privilégier.

### Déployer l'étape 3

Aucune migration. Après le déploiement, relancer une fois `catalog:migrate-variants` (sans risque) pour compléter les
produits créés entre-temps — la fiche produit reconstitue de toute façon les options manquantes à la première
modification. Retour arrière possible vers la version précédente : `color_id` reste rempli (option « Couleur », sinon
la couleur technique « Sans variante »).

## Étape 4 (à faire quand l'étape 3 est validée en production)

Seule étape **irréversible**. Prérequis : aucun doublon (requête ci-dessus) et `catalog:migrate-variants` sans variante
à migrer.

1. Migration : `sku` et `signature` obligatoires, index unique `(product_id, signature)`, `color_id` facultatif,
   suppression de l'ancienne colonne `attributes`.
2. Code : retirer la double écriture de `color_id` (`VariantOptions::legacyColorId`) et la déduction des options
   depuis la couleur dans `ProductColor::booted()`.
3. Décider du sort de l'écran **Couleurs** (aujourd'hui simple dictionnaire utilisé par la double écriture).

## Tests

Les tests tournent sur PostgreSQL (celui de Laragon en local, PostgreSQL 16 dans la CI GitHub), dans une base dédiée
`stockmanager_test` (voir `phpunit.xml` ; serveur, utilisateur et mot de passe viennent du `.env`).

```bash
# Une seule fois
psql -h 127.0.0.1 -U alpha -d postgres -c "CREATE DATABASE stockmanager_test"

# Lancer les tests
php artisan test
```

Les tests vident leur base : ils refusent de tourner sur une base dont le nom ne contient pas « test »
(garde-fou dans `tests/TestCase.php`), la base de développement `app` ne peut donc pas être effacée par erreur.
