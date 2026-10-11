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
| 3. Basculer | Nouveaux écrans produit (options → grille de variantes), libellé partout, import avec options | Oui | Revenir à la version précédente |
| 4. Nettoyer | `color_id` facultatif, `sku` et `signature` obligatoires, unicité `(product_id, signature)`, suppression de l'ancienne colonne `attributes` | Non | **Non** : à faire quand tout est validé |

Pendant la transition, le modèle `ProductColor` complète automatiquement `option_values`, `signature` et `sku`
à chaque enregistrement : les écrans actuels (basés sur la couleur) produisent déjà des variantes complètes.

## Procédure de production (étapes 1 et 2)

### 0. Répétition (obligatoire)

1. Lancer une sauvegarde et la télécharger (Paramètres › Sauvegardes), ou :
   ```bash
   docker compose -f compose.prod.yaml exec php-fpm php artisan app:run-backup --now
   ```
2. La restaurer dans une base PostgreSQL 16 jetable, en local :
   ```bash
   docker run -d --rm --name stockmanager-rehearsal -e POSTGRES_USER=test -e POSTGRES_PASSWORD=test \
     -e POSTGRES_DB=rehearsal -p 55433:5432 postgres:16
   # le zip de sauvegarde contient db-dumps/postgresql-<base>.sql
   docker exec -i stockmanager-rehearsal psql -U test -d rehearsal < postgresql-app.sql
   ```
3. Pointer l'application dessus (`DB_HOST=127.0.0.1`, `DB_PORT=55433`, `DB_DATABASE=rehearsal`,
   `DB_USERNAME=test`, `DB_PASSWORD=test`) puis jouer les étapes 1 et 2 ci-dessous et vérifier le rapport.

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

## Tester sur PostgreSQL en local

Les tests tournent par défaut sur SQLite. Pour les lancer sur PostgreSQL 16 (comme la production) :

```bash
docker run -d --rm --name stockmanager-test-db -e POSTGRES_USER=test -e POSTGRES_PASSWORD=test \
  -e POSTGRES_DB=stock_test -p 55432:5432 --tmpfs /var/lib/postgresql/data postgres:16

DB_CONNECTION=pgsql DB_HOST=127.0.0.1 DB_PORT=55432 DB_DATABASE=stock_test \
  DB_USERNAME=test DB_PASSWORD=test php vendor/bin/phpunit
```

(Sous Git Bash, préfixer la commande `docker run` par `MSYS_NO_PATHCONV=1`.)
