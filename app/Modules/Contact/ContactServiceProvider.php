<?php

namespace App\Modules\Contact;

use App\Core\Support\ModuleServiceProvider;

/**
 * Module Contact : fournisseurs et clients.
 * Les ventes d'un client appartiennent au module Sale (sales.customer_id) : Contact n'en dépend pas.
 */
class ContactServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'contact';
}
