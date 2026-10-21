<?php

namespace App\Modules\Sale\Models;

use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Sale\Database\Factories\SaleItemFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[UseFactory(SaleItemFactory::class)]
class SaleItem extends Model
{
    use HasFactory;

    // unit_cost : coût d'achat unitaire figé à la vente (vide si le coût n'était pas connu)
    protected $fillable = ['sale_id', 'product_color_id', 'quantity', 'unit_price', 'unit_cost', 'subtotal'];

    /**
     * Relation vers la variante spécifique (Produit + Couleur)
     */
    public function productColor()
    {
        return $this->belongsTo(ProductColor::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
