<?php

// Documents PDF : gabarit commun (layouts.pdf), facture, bon de commande

return [
    // Pied de page
    'page' => 'Page :page / :pages',
    'generated_on' => 'Édité le :date',
    'generated_by' => 'par :name',
    'registration_number' => 'NIF / SIRET',
    'vat_number' => 'N° TVA',

    // En-tête et parties
    'invoice' => 'Facture',
    'purchase_order' => 'Bon de commande',
    'reference' => 'Référence',
    'date' => 'Date',
    'status' => 'Statut',
    'supplier_reference' => 'Réf. fournisseur',
    'billed_to' => 'Facturé à',
    'ordered_from' => 'Commandé à',
    'seller' => 'Vendeur',
    'not_specified' => 'Non renseigné',

    // Lignes et totaux
    'item' => 'Article',
    'sku' => 'SKU',
    'quantity' => 'Qté',
    'unit_price' => 'Prix unitaire',
    'amount' => 'Montant',
    'subtotal' => 'Sous-total',
    'discount' => 'Remise',
    'total_due' => 'Net à payer',
    // TVA (prix TTC, taux figé sur la vente)
    'unit_price_incl_vat' => 'Prix unitaire TTC',
    'total_excl_vat' => 'Total HT',
    'vat' => 'TVA :rate %',
    'total_due_incl_vat' => 'Net à payer TTC',
    'order_total' => 'Total de la commande',
    'transaction' => 'Transaction',
    'paid_by' => 'Payé par',
    'received' => 'Reçu',
    'change' => 'Rendu',
    'notes' => 'Notes',
    'thanks' => 'Merci de votre achat.',

    // Statut d'un achat
    'states' => [
        'Draft' => 'Brouillon',
        'Ordered' => 'Commandé',
        'PartiallyReceived' => 'Partiellement reçu',
        'Received' => 'Reçu',
    ],
];
