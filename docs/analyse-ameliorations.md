# Analyse et plan d'amélioration

Analyse des fonctionnalités, de la logique métier et de l'expérience utilisateur de StockManager (Vitrine),
suivie d'un plan d'amélioration priorisé et de nouvelles idées.

- **Date** : 21-10-2026, sur le commit `f732897` (branche `main`).
- **Méthode** : lecture de la documentation, des services, des modèles, des migrations, des routes et des
  principales vues. Rien n'a été exécuté : les problèmes listés viennent du code, pas d'essais dans l'application.

## Sommaire

1. [Ce qui est déjà solide](#1-ce-qui-est-déjà-solide)
2. [Problèmes de logique métier](#2-problèmes-de-logique-métier)
3. [Expérience utilisateur](#3-expérience-utilisateur)
4. [Plan d'amélioration](#4-plan-damélioration)
5. [Nouvelles idées](#5-nouvelles-idées)
6. [Recommandation](#6-recommandation)

---

## 1. Ce qui est déjà solide

- **Architecture** : modules séparés (`app/Modules/*`) avec des règles de dépendance, et sécurité des routes
  déduite du nom du fichier (`admin.php`, `saler.php`, `api.php`). Voir [architecture.md](architecture.md).
- **Stock** : il ne bouge que par des mouvements, écrits par
  [StockService](../app/Modules/Stock/Services/StockService.php) seul, avec verrous `lockForUpdate` et
  transactions sur toutes les opérations critiques.
- **Données** : on archive au lieu de supprimer, et les clés étrangères d'historique sont en `ON DELETE RESTRICT`.
  Ventes, achats, mouvements et inventaires restent complets.
- **Caisse** : utilisable au clavier et au scanner, panier gardé sur le poste, ticket 80 mm imprimé
  automatiquement. Voir [flux-vente.md](flux-vente.md).
- **Exports** : chaque liste s'exporte en PDF et en Excel avec les mêmes filtres que la page.
- **Tests** : 52 fichiers de tests, lancés sur SQLite et PostgreSQL 16 en intégration continue.

Les fondations sont saines. Le problème est ailleurs : plusieurs règles métier sont incomplètes, et c'est là que
se trouve la valeur.

---

## 2. Problèmes de logique métier

Classés du plus grave au moins grave.

| # | Problème | Où | Conséquence concrète |
|---|---|---|---|
| 1 | Une vente ne peut pas être annulée ni faire l'objet d'un retour | [Sale/Routes/admin.php](../app/Modules/Sale/Routes/admin.php) : seulement `index`, `create`, `store`, `show` | Une erreur de caisse se corrige avec une « Correction » de stock, mais le chiffre d'affaires reste faux. |
| 2 | Le coût d'achat n'est pas enregistré sur la vente | table `sale_items` : seulement `unit_price` | Impossible de calculer la marge réelle d'une vente ou d'une période. |
| 3 | La réception d'un achat ne met pas à jour le coût de la variante | [PurchaseService::changeState](../app/Modules/Purchase/Services/PurchaseService.php) | `price_purchase` reste figé. Le réglage « CUMP / FIFO » n'a aucun effet. |
| 4 | Le stock est valorisé au prix de vente | [StockService::getTotalStockValueEvolution](../app/Modules/Stock/Services/StockService.php) | La valeur affichée est surestimée de toute la marge. |
| 5 | L'alerte « chute de stock » se déclenche sur les ventes | [MonitorStockDrop](../app/Modules/Stock/Console/Commands/MonitorStockDrop.php) | Une bonne journée de ventes envoie une alerte « vol suspect ». Les vraies pertes (corrections, écarts d'inventaire) ne sont pas suivies. |
| 6 | Vendre pendant un inventaire fausse le stock | [InventoryService::validate](../app/Modules/Inventory/Services/InventoryService.php) : `adjustTo(counted_quantity)` | On compte 10 articles, on en vend 3, puis on valide : le stock revient à 10 au lieu de 7. |
| 7 | Les ruptures ignorent les commandes déjà passées | [PurchaseService::getShortageProductsGroupedBySupplier](../app/Modules/Purchase/Services/PurchaseService.php) | Un article déjà au statut « Commandé » est reproposé, d'où un risque de commande en double. |
| 8 | Pas de réception partielle | `changeState('Received')` réceptionne toutes les lignes | Si le fournisseur livre 80 articles sur 100, on ne peut pas l'enregistrer correctement. |
| 9 | Le type d'un mouvement est deviné à partir du texte du motif, et l'auteur n'est pas enregistré | [StockMovement::kind()](../app/Modules/Stock/Models/StockMovement.php) ; table `stock_movements` sans `user_id` | L'audit est fragile (un motif reformulé change le type affiché) et on ne sait pas qui a fait une correction. |
| 10 | Les numéros de vente sont aléatoires | [SaleService::createSale](../app/Modules/Sale/Services/SaleService.php) : `'SALE-' . Str::random(8)` | Les factures ne sont pas numérotées à la suite, ce qui est généralement exigé. |
| 11 | Pas de limite de tentatives de connexion | [AuthController::login](../app/Core/Http/Controllers/AuthController.php), aucun `RateLimiter` | Les mots de passe peuvent être attaqués par force brute. |

### Détails

**Inventaire (n° 6).** `expected_quantity` est figée à l'ouverture de l'inventaire, et la validation ramène le stock
à la quantité comptée. Les ventes faites entre le comptage d'une ligne et la validation sont donc effacées.
Correction proposée : enregistrer le stock de la variante au moment où sa ligne est comptée (`stock_at_count`),
puis appliquer à la validation l'écart `counted_quantity − stock_at_count` au stock courant.

**Ruptures (n° 7).** La requête compare `stock <= alert_stock` sans tenir compte des lignes d'achats au statut
`Ordered`. La quantité suggérée (`seuil − stock + 50 % du seuil`) ne tient pas compte non plus de ce qui arrive.
La sous-requête du « dernier fournisseur » prend aussi en compte les brouillons.

**Alerte de chute (n° 5).** La commande compare la valeur totale du stock entre hier et aujourd'hui. Cette valeur
baisse à chaque vente : l'alerte confond activité normale et perte. Elle utilise aussi le prix actuel pour les
mouvements passés.

---

## 3. Expérience utilisateur

- **Langues mélangées.** Seules 15 vues sur 99 utilisent `__()`. L'interface est écrite en anglais en dur, alors que
  les messages d'erreur des services sont en français et que l'activité est malgache (MGA). Les fichiers
  `lang/fr`, `lang/en` et `lang/es` existent déjà, mais ne couvrent que le menu, la caisse et les documents PDF.
- **Cloche de notifications toujours vide.** La barre d'application affiche des notifications
  ([tools.blade.php](../resources/views/layouts/back-office/tools.blade.php)), mais
  [LowStockAlert](../app/Modules/Stock/Notifications/LowStockAlert.php) n'est jamais envoyée. Elle lit en plus
  des champs qui n'existent plus (`quantity_stock`, `name` sur la variante).
- **Réglages sans effet.** « Taux de TVA par défaut » et « Méthode de valorisation » s'enregistrent
  ([SettingController](../app/Core/Http/Controllers/SettingController.php)), mais aucun code ne les utilise.
  La TVA n'apparaît pas sur les factures. L'utilisateur croit qu'ils fonctionnent.
- **Tableau de bord peu utile pour décider.** Il affiche le nombre de catégories et de fournisseurs, mais pas de
  marge, pas de commandes à recevoir, pas de valeur du stock dormant
  ([DashboardService](../app/Core/Services/DashboardService.php)). Le « moins vendu » ne compte que les produits
  qui ont déjà été vendus au moins une fois.
- **Espace vendeur.** Il n'y a pas de clôture de caisse : le vendeur ne peut pas comparer les espèces en caisse
  avec ce que le système attend. Son tableau de bord se limite aux ventes du jour, du mois et à leur nombre.
- **Rôles.** Deux rôles seulement (`back_office`, `front_office`). Un magasinier qui réceptionne et compte le stock
  doit avoir un accès administrateur complet : paramètres, utilisateurs, coûts.

---

## 4. Plan d'amélioration

### Phase 1 : fiabilité (environ 1 à 2 jours, peu risqué)

1. Limiter les tentatives de connexion : 5 essais par minute, par email et par adresse IP (`RateLimiter`).
2. Inventaire : enregistrer le stock au moment où chaque ligne est comptée, puis appliquer l'écart
   `compté − stock au comptage` (voir les détails du n° 6).
3. Ruptures : retirer les quantités déjà commandées de la suggestion, et afficher « commandé : X ».
4. Alerte : surveiller les pertes non expliquées (corrections en sortie et écarts d'inventaire négatifs) au lieu de
   la valeur totale du stock.
5. Brancher la cloche de notifications sur les passages sous le seuil d'alerte, ou la retirer.

### Phase 2 : coûts et marges (le plus utile pour gérer)

1. Ajouter `unit_cost` sur `sale_items`, figé au moment de la vente.
2. À chaque réception, mettre à jour le coût moyen pondéré (CUMP) de la variante :
   `(stock × ancien coût + quantité reçue × prix d'achat) / nouveau stock`.
3. Valoriser le stock au coût. Afficher la marge brute (en montant et en %) sur le tableau de bord, la fiche
   produit et la liste des ventes.
4. Supprimer l'option FIFO tant qu'elle n'est pas réellement calculée.

### Phase 3 : cycle de vente complet

1. Annulation de vente et avoir partiel (retour d'articles) : remise en stock par un mouvement, montant négatif
   dans le chiffre d'affaires, réservé au back office.
2. Numérotation à la suite (`FAC-2026-000123`), avec un compteur par année.
3. Ajouter un `user_id` et une source précise (`source_type` / `source_id`) aux mouvements de stock, à la place de
   la lecture du motif.

### Phase 4 : expérience utilisateur

1. Traduire toutes les vues en français (les fichiers `lang/` existent déjà).
2. Un tableau de bord orienté action : ventes du jour, marge, commandes à recevoir, ruptures, stock dormant.
3. Réception partielle des achats, avec suivi du reste à livrer.
4. Appliquer la TVA sur les factures, ou retirer le réglage.

---

## 5. Nouvelles idées

Classées de la plus utile à la moins utile pour un commerce à Madagascar.

1. **Sessions de caisse.** Fonds de caisse à l'ouverture, clôture en fin de journée (total par mode de paiement),
   écart sur les espèces signalé au back office.
2. **Vente à crédit et dettes clients.** Paiements partiels, solde dû par client, relances. Très courant dans le
   commerce de détail local.
3. **Mobile Money détaillé.** Choix de l'opérateur (MVola, Orange Money, Airtel Money) et numéro de transaction sur
   la vente, pour pouvoir rapprocher les paiements.
4. **Réapprovisionnement intelligent.** Vitesse de vente sur 30 ou 90 jours, nombre de jours de stock restants,
   quantité à commander selon le délai du fournisseur, au lieu de la règle actuelle `seuil × 1,5`.
5. **Étiquettes code-barres.** Imprimer les codes SKU en planche, puisque la caisse sait déjà les scanner.
6. **Analyse ABC** des produits (les 20 % qui font 80 % du chiffre) et rapport du stock dormant valorisé au coût
   (`getDormantProducts` existe déjà sur la page des mouvements).
7. **Résumé quotidien** envoyé à 20 h par e-mail ou WhatsApp : chiffre d'affaires, marge, ruptures, écart de caisse.
8. **Promotions.** Prix par quantité, remise par catégorie, période promotionnelle.
9. **Rôles supplémentaires.** Magasinier (réceptions, inventaires, corrections) et gérant (rapports sans
   paramètres ni utilisateurs).
10. **Plus tard.** Plusieurs boutiques ou dépôts avec transferts de stock, et une caisse qui fonctionne hors
    connexion (le panier est déjà gardé sur le poste).

---

## 6. Recommandation

Commencer par **la phase 1, puis le coût figé sur les ventes et le CUMP (phase 2, points 1 et 2)**.
Ce sont des corrections courtes qui rendent justes les chiffres que l'application affiche déjà. Sans elles, toute
nouvelle fonction de reporting s'appuierait sur des données fausses.

Chaque point se traite sur une branche dédiée, avec ses tests dans `tests/Feature/<Module>/`.
