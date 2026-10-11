<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use App\Modules\Catalog\Database\Factories\ProductColorFactory;
use App\Modules\Catalog\Support\VariantOptions;

/**
 * Variante vendable d'un produit (table historique product_colors, référencée par les ventes,
 * achats, mouvements de stock et inventaires).
 *
 * Variantes flexibles : la combinaison est dans option_values ({"Contenance":"25 ml"}),
 * la marque dans brand_id. color_id est conservé pendant la transition.
 * (L'ancienne colonne JSON « attributes » n'est pas utilisée : son nom entre en conflit avec Eloquent.)
 */
#[UseFactory(ProductColorFactory::class)]
class ProductColor extends Model
{
    use HasFactory;

    protected $table = 'product_colors';

    protected $fillable = [
        'product_id', 'color_id', 'stock', 'alert_stock', 'price', 'price_purchase', 'is_active',
        'option_values', 'signature', 'sku', 'brand_id',
    ];

    protected function casts(): array
    {
        return [
            'option_values' => 'array',
        ];
    }

    protected static function booted(): void
    {
        // Transition : tant que les écrans travaillent avec la couleur, la combinaison d'options,
        // la signature et le SKU sont complétés automatiquement à chaque enregistrement.
        static::saving(function (ProductColor $variant) {
            $colorChanged = $variant->isDirty('color_id') && !$variant->isDirty('option_values');
            if ($variant->color_id && ($variant->option_values === null || $colorChanged)) {
                $variant->option_values = VariantOptions::fromColor($variant->color()->first());
            }

            if ($variant->option_values !== null && ($variant->signature === null || $variant->isDirty(['option_values', 'brand_id']))) {
                $variant->signature = VariantOptions::signature($variant->option_values, $variant->brand_id);
            }

            if (!$variant->sku && $variant->product_id && $variant->option_values !== null) {
                $product = $variant->product()->first(['name', 'options']);
                $variant->sku = VariantOptions::generateSku(
                    $product->name ?? 'ARTICLE',
                    $variant->option_values,
                    $variant->brand()->value('name'),
                    $variant->id,
                    productOptions: $product?->options
                );
            }
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function color()
    {
        return $this->belongsTo(Color::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    /** Libellé complet : "Postiche lisse — Noir / 30 cm — Bella". */
    public function getLabelAttribute(): string
    {
        return VariantOptions::label(
            $this->product->name ?? '',
            $this->option_values ?? VariantOptions::fromColor($this->color),
            $this->brand?->name,
            $this->product?->options
        );
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
        // Regroupé entre parenthèses : sinon les OR contournent les autres filtres de la requête
        $query->where(function ($query) use ($term) {
            $query->whereHas('product', function($q) use ($term) {
                $q->whereRaw('LOWER(name) LIKE ?', [$term]);
            })->orWhereHas('color', function($q) use ($term) {
                $q->whereRaw('LOWER(name) LIKE ?', [$term]);
            })->orWhereHas('product.category', function($q) use ($term) {
                $q->whereRaw('LOWER(name) LIKE ?', [$term]);
            });
        });
    }
}