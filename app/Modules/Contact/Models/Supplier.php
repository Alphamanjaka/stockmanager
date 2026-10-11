<?php

namespace App\Modules\Contact\Models;

use App\Modules\Contact\Database\Factories\SupplierFactory;
use App\Modules\Purchase\Models\Purchase;
use Illuminate\Database\Eloquent\Attributes\UseFactory;

/**
 * Fournisseur. Coordonnées et recherche : voir Contact.
 */
#[UseFactory(SupplierFactory::class)]
class Supplier extends Contact
{
    protected $table = 'suppliers';

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
}
