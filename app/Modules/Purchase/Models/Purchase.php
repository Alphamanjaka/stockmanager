<?php

namespace App\Modules\Purchase\Models;

use App\Modules\Contact\Models\Supplier;
use App\Modules\Purchase\Database\Factories\PurchaseFactory;
use App\Modules\Stock\Models\StockMovement;
use App\Modules\Stock\Support\MovementSource;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[UseFactory(PurchaseFactory::class)]
class Purchase extends Model
{
    use HasFactory;

    /** Statuts, enregistrés en anglais : brouillon → commandé → partiellement reçu → reçu. */
    public const DRAFT = 'Draft';

    public const ORDERED = 'Ordered';

    /** Une partie des articles est livrée, le reste est attendu. */
    public const PARTIALLY_RECEIVED = 'PartiallyReceived';

    /** Statut final : tout est livré, ou le reste a été abandonné (commande soldée). */
    public const RECEIVED = 'Received';

    public const STATES = [self::DRAFT, self::ORDERED, self::PARTIALLY_RECEIVED, self::RECEIVED];

    /** Statuts d'un achat qui a fait entrer du stock. */
    public const WITH_RECEPTIONS = [self::PARTIALLY_RECEIVED, self::RECEIVED];

    // define fillable fields
    protected $fillable = [
        'reference',
        'supplier_reference', // n° de facture ou de bon du fournisseur
        'total_amount',
        'discount',
        'total_net',
        'supplier_id',
        'state',
        'notes',
    ];

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /** Entrées en stock de ses livraisons : un mouvement par ligne livrée, avec sa date et son auteur. */
    public function receptions()
    {
        return $this->hasMany(StockMovement::class, 'source_id')
            ->where('source_type', MovementSource::PURCHASE)
            ->where('type', 'in');
    }

    /** @return array<string, string> statut => libellé dans la langue de l'application */
    public static function stateLabels(): array
    {
        return [
            self::DRAFT => __('Draft'),
            self::ORDERED => __('Ordered'),
            self::PARTIALLY_RECEIVED => __('Partially received'),
            self::RECEIVED => __('Received'),
        ];
    }

    public function stateLabel(): string
    {
        return self::stateLabels()[$this->state] ?? (string) $this->state;
    }

    /** Commande en cours : des articles restent attendus (commandé, ou partiellement reçu). */
    public function isAwaitingDelivery(): bool
    {
        return in_array($this->state, [self::ORDERED, self::PARTIALLY_RECEIVED], true);
    }

    /** Achats en cours : commandés ou partiellement reçus. */
    public function scopeAwaitingDelivery(Builder $query): Builder
    {
        return $query->whereIn('state', [self::ORDERED, self::PARTIALLY_RECEIVED]);
    }

    /** Remise de l'achat répartie sur ses lignes : coût réellement payé = prix de la ligne × ce ratio. */
    public function netRatio(): float
    {
        return (float) $this->total_amount > 0 ? (float) $this->total_net / (float) $this->total_amount : 1.0;
    }

    /** Articles encore attendus (commande en cours) ou jamais livrés (commande soldée). */
    public function remainingQuantity(): int
    {
        return (int) $this->items->sum(fn (PurchaseItem $item) => $item->remainingQuantity());
    }

    /** Valeur des articles reçus, remise déduite. */
    public function receivedValue(): float
    {
        return round($this->items->sum(fn (PurchaseItem $item) => $item->received_quantity * (float) $item->unit_price) * $this->netRatio(), 2);
    }

    /** Valeur des articles encore attendus, remise déduite. */
    public function remainingValue(): float
    {
        return round($this->items->sum(fn (PurchaseItem $item) => $item->remainingQuantity() * (float) $item->unit_price) * $this->netRatio(), 2);
    }
}
