<?php

// Documents PDF : gabarit commun (layouts.pdf), facture, bon de commande

return [
    // Pied de page
    'page' => 'Page :page / :pages',
    'generated_on' => 'Generated on :date',
    'generated_by' => 'by :name',
    'registration_number' => 'Reg. no.',
    'vat_number' => 'VAT no.',

    // En-tête et parties
    'invoice' => 'Invoice',
    'purchase_order' => 'Purchase order',
    'reference' => 'Reference',
    'date' => 'Date',
    'status' => 'Status',
    'supplier_reference' => 'Supplier ref.',
    'billed_to' => 'Billed to',
    'ordered_from' => 'Ordered from',
    'seller' => 'Seller',
    'not_specified' => 'Not specified',

    // Lignes et totaux
    'item' => 'Item',
    'sku' => 'SKU',
    'quantity' => 'Qty',
    'unit_price' => 'Unit price',
    'amount' => 'Amount',
    'subtotal' => 'Subtotal',
    'discount' => 'Discount',
    'total_due' => 'Total due',
    // TVA (prix TTC, taux figé sur la vente)
    'unit_price_incl_vat' => 'Unit price incl. VAT',
    'total_excl_vat' => 'Total excl. VAT',
    'vat' => 'VAT :rate %',
    'total_due_incl_vat' => 'Total due incl. VAT',
    'order_total' => 'Order total',
    'transaction' => 'Transaction',
    'paid_by' => 'Paid by',
    'received' => 'Received',
    'change' => 'Change',
    'notes' => 'Notes',
    'thanks' => 'Thank you for your purchase.',

    // Statut d'un achat
    'states' => [
        'Draft' => 'Draft',
        'Ordered' => 'Ordered',
        'PartiallyReceived' => 'Partially received',
        'Received' => 'Received',
    ],
];
