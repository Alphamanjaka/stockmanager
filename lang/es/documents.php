<?php

// Documents PDF : gabarit commun (layouts.pdf), facture, bon de commande

return [
    // Pied de page
    'page' => 'Página :page / :pages',
    'generated_on' => 'Generado el :date',
    'generated_by' => 'por :name',
    'registration_number' => 'N.º registro',
    'vat_number' => 'N.º IVA',

    // En-tête et parties
    'invoice' => 'Factura',
    'purchase_order' => 'Orden de compra',
    'reference' => 'Referencia',
    'date' => 'Fecha',
    'status' => 'Estado',
    'supplier_reference' => 'Ref. proveedor',
    'billed_to' => 'Facturado a',
    'ordered_from' => 'Pedido a',
    'seller' => 'Vendedor',
    'not_specified' => 'No indicado',

    // Lignes et totaux
    'item' => 'Artículo',
    'sku' => 'SKU',
    'quantity' => 'Cant.',
    'unit_price' => 'Precio unitario',
    'amount' => 'Importe',
    'subtotal' => 'Subtotal',
    'discount' => 'Descuento',
    'total_due' => 'Total a pagar',
    // TVA (prix TTC, taux figé sur la vente)
    'unit_price_incl_vat' => 'Precio unitario IVA incl.',
    'total_excl_vat' => 'Total sin IVA',
    'vat' => 'IVA :rate %',
    'total_due_incl_vat' => 'Total a pagar IVA incl.',
    'order_total' => 'Total del pedido',
    'paid_by' => 'Pagado con',
    'received' => 'Recibido',
    'change' => 'Cambio',
    'notes' => 'Notas',
    'thanks' => 'Gracias por su compra.',

    // Statut d'un achat
    'states' => [
        'Draft' => 'Borrador',
        'Ordered' => 'Pedido',
        'PartiallyReceived' => 'Recibido en parte',
        'Received' => 'Recibido',
    ],
];
