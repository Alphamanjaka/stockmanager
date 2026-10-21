<?php

// Écran de caisse et reçu (module Sale)

return [
    'title' => 'Caisse',

    // Recherche et filtres
    'search_label' => 'Rechercher un produit',
    'search_placeholder' => 'Scannez un code ou tapez un nom de produit…',
    'clear_search' => 'Effacer la recherche',
    'categories' => 'Catégories',
    'all' => 'Tout',
    'top_sellers' => 'Les plus vendus',
    'brands' => 'Marques',
    'all_brands' => 'Toutes',
    'quantity_multiplier' => 'Quantité du prochain article',

    // Panier
    'cart' => 'Panier',
    'cart_empty_title' => 'Le panier est vide',
    'cart_empty_hint' => 'Scannez un article, tapez son nom ou touchez une vignette.',
    'total' => 'Total',
    'pay' => 'Encaisser',
    'cancel_sale' => 'Annuler la vente',
    'settings' => 'Réglages de la caisse',
    'sound' => 'Bip à chaque article',
    'auto_print' => 'Imprimer le reçu automatiquement',

    // Aide clavier
    'shortcuts_title' => 'Raccourcis clavier',
    'keys' => ['enter' => 'Entrée', 'esc' => 'Échap', 'del' => 'Suppr'],
    'shortcuts' => [
        'search' => 'Rechercher',
        'multiply' => 'Quantité puis article',
        'qty' => 'Quantité de la ligne',
        'lines' => 'Choisir une ligne',
        'remove' => 'Retirer la ligne',
        'cancel' => 'Annuler la vente',
        'pay' => 'Encaisser',
        'clear' => 'Effacer',
    ],

    // Encaissement
    'payment_title' => 'Encaissement',
    'amount_due' => 'À payer',
    'subtotal' => 'Sous-total',
    'discount' => 'Remise',
    'method' => 'Mode de paiement',
    'methods' => [
        'cash' => 'Espèces',
        'mobile_money' => 'Mobile Money',
        'card' => 'Carte bancaire',
    ],
    'amount_received' => 'Somme reçue',
    'amount_received_hint' => 'Laissez vide si le client donne le montant exact.',
    'exact_amount' => 'Montant exact',
    'change_due' => 'Monnaie à rendre',
    'customer_optional' => 'Client (facultatif)',
    'back' => 'Retour',
    'validate' => 'Valider la vente',

    // Vente terminée
    'success_title' => 'Vente enregistrée',
    'print_receipt' => 'Imprimer le reçu',
    'new_sale' => 'Nouvelle vente',
    'view_sale' => 'Voir la fiche de la vente',

    // Client
    'walk_in' => '— Client de passage —',
    'new_customer' => '+ Nouveau client',
    'customer_name' => 'Nom',
    'customer_phone' => 'Téléphone',
    'new_customer_hint' => 'Utilisé seulement si aucun client existant n\'est choisi.',

    // Reçu
    'receipt' => 'Reçu',
    'receipt_number' => 'Reçu n°',
    'total_excl_vat' => 'Total HT',
    'vat' => 'TVA :rate %',
    'total_incl_vat' => 'Total TTC',
    'date' => 'Date',
    'cashier' => 'Caissier',
    'customer' => 'Client',
    'paid_by' => 'Payé par',
    'received' => 'Somme reçue',
    'change' => 'Rendu',
    'thanks' => 'Merci de votre visite !',
    'print' => 'Imprimer',
    'close' => 'Fermer',

    // Messages affichés par la caisse (JavaScript)
    'js' => [
        'item' => 'article',
        'items' => 'articles',
        'in_stock' => ':count en stock',
        'out_of_stock' => 'Rupture',
        'in_cart' => 'dans le panier',
        'sold_out' => '« :name » est en rupture de stock.',
        'not_enough_stock' => 'Stock insuffisant pour « :name » : :count disponible(s).',
        'not_found' => 'Aucun produit ne correspond à « :query ».',
        'no_results' => 'Aucun produit dans cette sélection.',
        'more_results' => 'Et :count autres : précisez la recherche ou choisissez une catégorie.',
        'removed' => '« :name » retiré du panier.',
        'cart_restored' => 'Le panier en cours a été restauré.',
        'cart_empty_pay' => 'Ajoutez au moins un article avant d\'encaisser.',
        'confirm_cancel' => 'Annuler la vente en cours ?',
        'confirm_cancel_text' => 'Tous les articles du panier seront retirés.',
        'confirm_cancel_yes' => 'Oui, vider le panier',
        'confirm_cancel_no' => 'Non, continuer',
        'decrease' => 'Diminuer la quantité',
        'increase' => 'Augmenter la quantité',
        'remove' => 'Retirer du panier',
        'quantity' => 'Quantité',
        'change_due' => 'Monnaie à rendre',
        'missing' => 'Il manque',
        'paid_exact' => 'Montant exact : rien à rendre',
        'sale_failed' => 'La vente n\'a pas été enregistrée',
        'network_error' => 'Le serveur ne répond pas. Vérifiez la connexion puis réessayez : rien n\'a été enregistré.',
        'session_expired' => 'Votre session a expiré. Rechargez la page : le panier sera conservé.',
    ],
];
