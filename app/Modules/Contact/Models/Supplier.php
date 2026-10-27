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

    protected $casts = ['lead_time_days' => 'integer'];

    /** Coordonnées communes (Contact), plus le délai de livraison en jours (vide : délai par défaut). */
    public function __construct(array $attributes = [])
    {
        $this->mergeFillable(['lead_time_days']);

        parent::__construct($attributes);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
}
