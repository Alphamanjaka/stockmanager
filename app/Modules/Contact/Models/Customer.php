<?php

namespace App\Modules\Contact\Models;

use App\Modules\Contact\Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;

/**
 * Client final. Coordonnées et recherche : voir Contact.
 * Les ventes qui lui sont rattachées appartiennent au module Sale (sales.customer_id).
 */
#[UseFactory(CustomerFactory::class)]
class Customer extends Contact
{
    protected $table = 'customers';
}
