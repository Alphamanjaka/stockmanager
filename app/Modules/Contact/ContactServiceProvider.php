<?php

namespace App\Modules\Contact;

use App\Core\Support\ModuleServiceProvider;
use App\Modules\Contact\Exports\CustomersExport;
use App\Modules\Contact\Exports\SuppliersExport;

/**
 * Module Contact : fournisseurs et clients.
 * Les ventes d'un client appartiennent au module Sale (sales.customer_id) : Contact n'en dépend pas.
 */
class ContactServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'contact';

    protected array $exports = [
        'suppliers' => SuppliersExport::class,
        'customers' => CustomersExport::class,
    ];

    /** Les contacts sont rangés là où on s'en sert : fournisseurs dans Achats, clients dans Ventes (après leurs pages). */
    protected array $menu = [
        'purchase' => [
            ['label' => 'backoffice.suppliers', 'icon' => 'bi-truck', 'route' => 'admin.suppliers.index', 'shortcut' => 'f', 'order' => 10],
        ],
        'sale' => [
            ['label' => 'backoffice.customers', 'icon' => 'bi-person-vcard', 'route' => 'admin.customers.index', 'order' => 10],
        ],
    ];
}
