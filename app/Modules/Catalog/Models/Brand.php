<?php

namespace App\Modules\Catalog\Models;

use App\Core\Models\Concerns\Archivable;
use App\Modules\Catalog\Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Marque. Portée par la variante (product_colors.brand_id) : une même postiche peut exister
 * en plusieurs marques, et les rapports « ventes par marque » passent par la variante vendue.
 */
#[UseFactory(BrandFactory::class)]
class Brand extends Model
{
    use HasFactory, Archivable;

    protected $fillable = ['name'];

    public function variants()
    {
        return $this->hasMany(ProductColor::class);
    }
}
