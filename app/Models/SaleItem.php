<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory;
    protected $fillable = ['sale_id', 'product_color_id', 'quantity', 'unit_price', 'subtotal'];

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