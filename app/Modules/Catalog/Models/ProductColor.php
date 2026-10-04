<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use App\Modules\Catalog\Database\Factories\ProductColorFactory;

#[UseFactory(ProductColorFactory::class)]
class ProductColor extends Model
{
    use HasFactory;

    protected $table = 'product_colors';

    protected $fillable = ['product_id', 'color_id', 'stock', 'alert_stock','price', 'price_purchase', 'attributes','is_active'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function color()
    {
        return $this->belongsTo(Color::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class, 'product_color_id');
    }
    public function toString()
    {
        return $this->product->name . ' ' . ($this->color->code ?? '');
    }
    public function getCategoryNameAttribute()
    {
        return $this->product->category->name ?? 'Non catégorisé';
    }

    // scope pour la recherche
    public function scopeSearch( $query, string $term){
        $term = '%' . strtolower($term) . '%';
        $query->whereHas('product', function($q) use ($term) {
            $q->whereRaw('LOWER(name) LIKE ?', [$term]);
        })->orWhereHas('color', function($q) use ($term) {
            $q->whereRaw('LOWER(name) LIKE ?', [$term]);
        })->orWhereHas('product.category', function($q) use ($term) {
            $q->whereRaw('LOWER(name) LIKE ?', [$term]);
        });
        ;
    }
}