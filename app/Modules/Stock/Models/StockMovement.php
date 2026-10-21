<?php

namespace App\Modules\Stock\Models;

use App\Models\User;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Stock\Database\Factories\StockMovementFactory;
use App\Modules\Stock\Support\MovementSource;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

#[UseFactory(StockMovementFactory::class)]
class StockMovement extends Model
{
    use HasFactory;

    /** Page du document à l'origine du mouvement, par type de source (routes des autres modules). */
    private const SOURCE_ROUTES = [
        MovementSource::PURCHASE => 'admin.purchases.show',
        MovementSource::SALE => 'admin.sales.show',
        MovementSource::INVENTORY => 'admin.inventories.show',
        MovementSource::SALE_RETURN => 'admin.sale-returns.show',
    ];

    protected $fillable = [
        'product_color_id',
        'quantity',
        'type',
        'reason',
        'stock_before',
        'stock_after',
        'user_id',
        'source_type',
        'source_id',
    ];

    /**
     * Get the product color variant that owns the stock movement.
     */
    public function productColor()
    {
        return $this->belongsTo(ProductColor::class);
    }

    /** Auteur de l'opération (vide : tâche planifiée, ou mouvement enregistré avant le suivi des auteurs). */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Lien vers le document à l'origine du mouvement (achat, vente, avoir, inventaire), ou null. */
    public function sourceUrl(): ?string
    {
        $route = self::SOURCE_ROUTES[$this->source_type] ?? null;

        return $route && $this->source_id && Route::has($route) ? route($route, $this->source_id) : null;
    }

    /**
     * Nature du mouvement, d'après sa source : [ton du badge, libellé].
     *
     * @return array{0: string, 1: string}
     */
    public function kind(): array
    {
        return match ($this->source_type) {
            MovementSource::CORRECTION => ['warning', __('Correction')],
            MovementSource::INVENTORY => ['info', __('Stock count')],
            MovementSource::PURCHASE => $this->type === 'in' ? ['success', __('Purchase')] : ['danger', __('Purchase cancelled')],
            MovementSource::SALE => ['danger', __('Sale')],
            MovementSource::SALE_RETURN => ['success', __('Sale return')],
            default => $this->kindFromReason(),
        };
    }

    /** Mouvement sans source (enregistré avant leur suivi, motif non reconnu) : nature déduite du motif, comme avant. */
    private function kindFromReason(): array
    {
        $reason = strtolower((string) $this->reason);

        return match (true) {
            str_starts_with($reason, 'correction') => ['warning', __('Correction')],
            str_starts_with($reason, 'inventaire') => ['info', __('Stock count')],
            str_contains($reason, 'ajustement') || str_contains($reason, 'manuel') => ['info', __('Adjustment')],
            $this->type === 'in' => ['success', str_contains($reason, 'achat') || str_contains($reason, 'pur') ? __('Purchase') : __('Stock in')],
            $this->type === 'out' => ['danger', str_contains($reason, 'vente') || str_contains($reason, 'sale') ? __('Sale') : __('Stock out')],
            default => ['neutral', __('Other')],
        };
    }
}
