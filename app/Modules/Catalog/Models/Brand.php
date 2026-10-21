<?php

namespace App\Modules\Catalog\Models;

use App\Core\Models\Concerns\Archivable;
use App\Modules\Catalog\Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Marque. Portée par la variante (product_colors.brand_id) : une même postiche peut exister
 * en plusieurs marques, et les rapports « ventes par marque » passent par la variante vendue.
 */
#[UseFactory(BrandFactory::class)]
class Brand extends Model
{
    use Archivable, HasFactory;

    protected $fillable = ['name'];

    public function variants()
    {
        return $this->hasMany(ProductColor::class);
    }

    /** Liste des marques (page et export) : recherche, statut (actives ou archives), nombre de variantes. */
    public function scopeForList(Builder $query, array $filters): void
    {
        $query->withCount('variants')
            ->withStatus($filters['status'] ?? null)
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->whereLike('name', "%{$search}%"))
            ->orderBy('name');
    }
}
