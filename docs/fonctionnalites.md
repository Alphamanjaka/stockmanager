# Vitrine : les fonctionnalités

Vitrine est un logiciel de gestion de boutique : caisse, stock, achats, clients et chiffres de gestion, dans un
seul outil utilisable depuis un navigateur. Il est pensé pour le commerce de détail à Madagascar : montants en
ariary, paiement Mobile Money, interface en français.

Ce document décrit ce que le logiciel fait aujourd'hui. Il est mis à jour à chaque nouvelle fonctionnalité ; les
dernières arrivées sont listées dans [Nouveautés](#nouveautés).

## Sommaire

1. [En bref](#en-bref)
2. [Caisse](#caisse)
3. [Ventes, factures et retours](#ventes-factures-et-retours)
4. [Clients](#clients)
5. [Catalogue](#catalogue)
6. [Stock](#stock)
7. [Inventaire](#inventaire)
8. [Achats et fournisseurs](#achats-et-fournisseurs)
9. [Tableau de bord et analyses](#tableau-de-bord-et-analyses)
10. [Alertes](#alertes)
11. [Import et export](#import-et-export)
12. [Paramètres](#paramètres)
13. [Utilisateurs et sécurité](#utilisateurs-et-sécurité)
14. [Sauvegardes](#sauvegardes)
15. [En préparation](#en-préparation)
16. [Nouveautés](#nouveautés)

---

## En bref

- **Une caisse rapide** : au scanner ou au clavier, ticket imprimé automatiquement, monnaie à rendre calculée.
- **Un stock toujours juste** : il ne bouge que par une vente, un achat reçu, un retour, un inventaire ou une
  correction justifiée. Chaque mouvement garde son auteur et son document d'origine.
- **De vraies marges** : le coût d'achat est suivi au coût moyen pondéré et figé sur chaque vente.
- **Rien ne se perd** : on archive au lieu de supprimer, l'historique des ventes et des achats reste complet.
- **Des chiffres pour décider** : ventes du jour, marge, ruptures, stock qui dort, produits qui font le chiffre.
- **Deux espaces** : le back office pour le gérant, la caisse pour les vendeurs, qui ne voient ni les coûts ni
  les paramètres.

---

## Caisse

- Recherche d'un article en scannant son code-barres (celui du fabricant ou celui du logiciel) ou en tapant son nom ; vignettes par catégorie et par marque,
  articles les plus vendus en tête.
- Utilisable entièrement au clavier : quantité avant l'article (`3` `*`), quantité d'une ligne (`+` `−`),
  encaissement (`F9`), annulation (`F4`).
- Le panier en cours est gardé sur le poste : une page rechargée ou une coupure ne fait pas perdre la vente.
- La vente est refusée si le stock est insuffisant, ou si l'article est retiré de la vente.
- Encaissement :
  - **Espèces** : somme reçue, billets proposés en un clic, monnaie à rendre affichée en direct ;
  - **Mobile Money** : opérateur (MVola, Orange Money, Airtel Money) et numéro de transaction obligatoires ; un
    même numéro ne peut pas payer deux ventes ;
  - **Carte bancaire**.
- Remise sur la vente, client existant ou créé sur place (nom et téléphone), ou client de passage.
- Ticket de caisse 80 mm imprimé automatiquement (désactivable), sans quitter la caisse.

## Ventes, factures et retours

- Numéros de facture à la suite, par année : `FAC-2026-000123`.
- Facture PDF à l'en-tête de l'entreprise (nom, adresse, NIF, logo), avec le mode de paiement et, en Mobile
  Money, l'opérateur et le numéro de transaction.
- Coordonnées de l'entreprise, devise et TVA figées sur chaque vente : une facture ancienne reste juste même si
  les paramètres changent.
- **TVA** (pour les entreprises assujetties, à activer dans les paramètres) : prix TTC, total HT et TVA sur la
  facture et le ticket.
- **Retours et annulation** : retour d'une partie des articles ou annulation complète, avec un avoir numéroté
  (`AVO-2026-000001`). Les articles reviennent en stock et le montant est déduit du chiffre d'affaires. Réservé
  au back office.
- Liste des ventes : recherche par numéro, client ou numéro de transaction Mobile Money ; filtre par période,
  client ou vendeur ; marge de chaque vente.
- Chaque vendeur ne voit que ses propres ventes.

## Clients

- Fiche client : coordonnées et historique des achats.
- Création depuis la caisse en une ligne (nom, téléphone).
- Archivage d'un client sans perdre ses ventes.

## Catalogue

- Produits avec catégorie, marque et options libres (couleur, taille, contenance…) : chaque combinaison est une
  variante avec son prix, son coût, son seuil d'alerte et son code (SKU) créé automatiquement, par exemple
  `POSTICHE-LISSE-NOIR-30CM`.
- Prix de vente proposé à partir du coût et d'un taux de marge par défaut.
- Catégories, marques et couleurs gérées à part.
- Archivage : un produit archivé n'est plus en vente mais reste dans l'historique.
- **Code-barres** de chaque variante : celui du fabricant (EAN-13, EAN-8, UPC-A), scanné ou importé, ou à défaut un
  code créé par le logiciel. Une faute de frappe ou un code déjà utilisé est refusé.
- **Étiquettes code-barres** : nom, prix et code-barres, sur planche A4 (24, 21 ou 40 étiquettes) ou sur rouleau
  pour imprimante thermique (50 × 30 mm). On peut reprendre une planche entamée, et préparer d'un clic les
  étiquettes d'un produit (une par article en stock) ou d'une livraison (une par article reçu).

## Stock

- Le stock ne se saisit jamais à la main : il évolue par les ventes, les achats reçus, les retours, les
  inventaires et les corrections (motif obligatoire).
- Historique des mouvements : date, nature, quantité, auteur, et lien vers la vente, l'achat ou l'inventaire
  d'origine.
- **Coût moyen pondéré** recalculé à chaque réception : le stock est valorisé au coût, pas au prix de vente.
- Évolution de la valeur du stock sur 30 jours, produits les plus sortis, stock dormant (aucun mouvement depuis
  60 jours) avec sa valeur.

## Inventaire

- Comptage physique, ligne par ligne, avec l'écart par rapport au stock.
- On peut continuer à vendre pendant l'inventaire : l'écart est mesuré au moment du comptage de chaque ligne,
  les ventes faites ensuite ne sont pas effacées à la validation.
- La validation corrige le stock par des mouvements « inventaire ».

## Achats et fournisseurs

- Bon de commande : fournisseur, référence fournisseur, notes, lignes modifiables, remise, totaux en direct.
  Coût proposé : le dernier prix payé à ce fournisseur.
- Étapes : brouillon, commandé, partiellement reçu, reçu. Bon de commande en PDF.
- **Réception partielle** : livraisons successives, reste à livrer suivi ; une commande peut être soldée si le
  reste ne viendra pas.
- **Réapprovisionnement** : liste des articles sous leur seuil, regroupés par dernier fournisseur, avec une
  quantité suggérée. Les quantités déjà commandées sont déduites, pour ne pas commander deux fois.
- Fiche fournisseur avec son historique d'achats.

## Tableau de bord et analyses

- **Back office** : ventes du jour, chiffre d'affaires, marge brute, commandes à recevoir, ruptures, stock
  dormant.
- **Vendeur** : ses ventes du jour et du mois.
- **Marge brute** en montant et en pourcentage, par vente, par produit et sur le tableau de bord, hors taxes et
  avoirs déduits.
- **Analyse ABC** : les produits classés par chiffre d'affaires sur 30, 90, 180 ou 365 jours.
  - A : ceux qui font les premiers 80 % du chiffre, à ne jamais laisser en rupture ;
  - B : les 15 % suivants ;
  - C : le reste et les produits qui ne se vendent pas, où le stock peut être réduit.
  
  Pour chaque classe : nombre de produits, part du chiffre d'affaires et valeur du stock au coût.

## Alertes

- Notification dans l'application quand un article passe sous son seuil d'alerte.
- Surveillance quotidienne des pertes non expliquées (corrections en sortie, écarts d'inventaire) : e-mail aux
  administrateurs au-delà d'un seuil. Une bonne journée de ventes ne déclenche pas d'alerte.

## Import et export

- **Import Excel** des produits, catégories, couleurs, fournisseurs et achats, avec un rapport d'erreurs ligne par
  ligne avant tout enregistrement.
- **Export PDF et Excel** de chaque liste, avec les mêmes filtres que l'écran, ou seulement les lignes cochées.

## Paramètres

- Entreprise : nom, adresse, téléphone, e-mail, NIF, n° de TVA, logo.
- Régional : langue (français ou anglais), fuseau horaire, format de date, devise et sa position.
- Stock et produits : seuil d'alerte par défaut, marge par défaut, TVA (assujetti ou non, taux).
- Interface : thème clair, sombre ou automatique, nombre de lignes par page.

## Utilisateurs et sécurité

- Deux rôles : administrateur (back office complet) et vendeur (caisse, ses ventes, consultation des produits sans
  les coûts).
- Connexion bloquée après 5 essais ratés par minute.
- Recherche globale dans le back office : produits, ventes, achats, mouvements et pages.

## Sauvegardes

- Sauvegarde automatique chaque nuit des fichiers et de la base, nettoyage des anciennes sauvegardes chaque
  semaine.

---

## En préparation

Classées de la plus simple à la plus complexe. Ces fonctionnalités ne sont pas encore disponibles.

1. Résumé quotidien par e-mail : chiffre d'affaires, marge, ruptures.
2. Réapprovisionnement selon la vitesse de vente et le délai du fournisseur.
3. Rôles supplémentaires : magasinier, gérant.
4. Sessions de caisse : fond de caisse, clôture, écart sur les espèces.
5. Promotions : prix par quantité, remises par catégorie, périodes.
6. Vente à crédit : paiements partiels, dettes clients, relances.
7. Plusieurs boutiques ou dépôts, caisse hors connexion.

---

## Nouveautés

Les plus récentes en premier.

### 27-10-2026

- **Code-barres** sur chaque produit (celui du fabricant ou un code interne), reconnu par la caisse.
- **Étiquettes code-barres** à imprimer, sur planche A4 ou sur rouleau (menu Produits > Étiquettes code-barres).

### 26-10-2026

- **Analyse ABC** des produits (menu Ventes > Analyse ABC).
- **Mobile Money détaillé** : opérateur et numéro de transaction sur chaque vente, sur le ticket et la facture,
  et recherche d'une vente par son numéro de transaction.

### 22-10-2026

- Interface entièrement en français.
- Tableau de bord orienté action : ventes du jour, marge, commandes à recevoir, ruptures, stock dormant.
- Réception partielle des achats.
- TVA sur les factures et les tickets, pour les entreprises assujetties.

### Octobre 2026

- Retours d'articles et annulation de vente, avec avoirs numérotés.
- Numéros de facture à la suite par année.
- Coût moyen pondéré, coût figé sur les ventes, marge brute partout.
- Auteur et document d'origine sur chaque mouvement de stock.
- Inventaire possible pendant les ventes.
- Réapprovisionnement sans commande en double.
- Alerte de pertes et notification des passages sous le seuil.
- Limite des tentatives de connexion.
