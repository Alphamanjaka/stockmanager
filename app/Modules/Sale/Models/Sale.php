<?php

namespace App\Modules\Sale\Models;

use App\Models\User;
use App\Modules\Contact\Models\Customer;
use App\Modules\Sale\Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[UseFactory(SaleFactory::class)]
class Sale extends Model
{
    use HasFactory;

    /** Modes de paiement acceptés en caisse (libellés : lang/xx/pos.php, clé methods). */
    public const PAYMENT_CASH = 'cash';

    public const PAYMENT_MOBILE_MONEY = 'mobile_money';

    public const PAYMENT_METHODS = [self::PAYMENT_CASH, self::PAYMENT_MOBILE_MONEY, 'card'];

    /** Opérateurs Mobile Money (libellés : lang/xx/pos.php, clé operators). */
    public const MOBILE_OPERATORS = ['mvola', 'orange_money', 'airtel_money'];

    /** Préfixe des numéros de facture, à la suite par année : FAC-2026-000123 (DocumentNumberService). */
    public const INVOICE_PREFIX = 'FAC';

    protected $fillable = ['reference', 'total_brut', 'discount', 'total_net', 'user_id', 'customer_id', 'company_name', 'company_email', 'company_phone', 'company_address', 'currency_symbol', 'vat_rate', 'payment_method', 'amount_received', 'payment_operator', 'payment_reference'];

    /**
     * La vente porte de la TVA : taux figé à la vente (réglage « Assujetti à la TVA »), prix TTC.
     * Sans taux (null ou 0), la facture n'en mentionne pas.
     */
    public function hasVat(): bool
    {
        return (float) $this->vat_rate > 0;
    }

    /** Taux de TVA affiché : « 20 » plutôt que « 20.00 ». */
    public function vatRateLabel(): string
    {
        return rtrim(rtrim(number_format((float) $this->vat_rate, 2, '.', ''), '0'), '.');
    }

    /** Montant hors taxes d'un montant TTC de la vente (inchangé sans TVA). */
    public function excludingVat(float $amount): float
    {
        return $this->hasVat() ? round($amount / (1 + (float) $this->vat_rate / 100), 2) : round($amount, 2);
    }

    /** TVA comprise dans un montant TTC de la vente : TTC − HT, pour que HT + TVA = TTC au centime près. */
    public function vatIncluded(float $amount): float
    {
        return round($amount - $this->excludingVat($amount), 2);
    }

    /** Mode de paiement affiché : « Mobile Money · MVola » quand l'opérateur est connu. */
    public function paymentLabel(): string
    {
        $label = __("pos.methods.{$this->payment_method}");

        return $this->payment_operator ? $label.' · '.__("pos.operators.{$this->payment_operator}") : $label;
    }

    /** Monnaie rendue au client : somme reçue moins le net à payer (0 si la somme reçue n'est pas connue). */
    public function getChangeDueAttribute(): float
    {
        return $this->amount_received === null ? 0.0 : max(0, round($this->amount_received - $this->total_net, 2));
    }

    /** Montant remboursé par les avoirs de la vente. */
    public function refundedTotal(): float
    {
        return round((float) $this->returns->sum('total'), 2);
    }

    /** Net encaissé, avoirs déduits. */
    public function netAfterReturns(): float
    {
        return round((float) $this->total_net - $this->refundedTotal(), 2);
    }

    /** Quantités déjà rendues, par ligne de vente : [id de ligne => quantité]. */
    public function returnedQuantities(): array
    {
        return $this->returns->flatMap->items
            ->groupBy('sale_item_id')
            ->map(fn ($items) => (int) $items->sum('quantity'))
            ->all();
    }

    /** 'returned' (tout est rendu : vente annulée), 'partially_returned', ou null sans retour. */
    public function returnStatus(): ?string
    {
        if ($this->returns->isEmpty()) {
            return null;
        }

        $returned = $this->returnedQuantities();
        $all = $this->items->every(fn (SaleItem $item) => ($returned[$item->id] ?? 0) >= $item->quantity);

        return $all ? 'returned' : 'partially_returned';
    }

    /**
     * Coût d'achat des articles gardés par le client (coûts figés à la vente, articles rendus déduits),
     * ou null si le coût d'une ligne n'est pas connu.
     */
    public function costTotal(): ?float
    {
        if ($this->items->contains(fn (SaleItem $item) => $item->unit_cost === null)) {
            return null;
        }

        $sold = $this->items->sum(fn (SaleItem $item) => $item->quantity * $item->unit_cost);
        $returned = $this->returns->sum(fn (SaleReturn $return) => $return->costTotal() ?? 0);

        return round((float) ($sold - $returned), 2);
    }

    /** Marge brute : net encaissé (remise et avoirs déduits) moins le coût des articles ; null si un coût manque. */
    /**
     * Marge brute : net encaissé (avoirs déduits) hors taxes, moins le coût des articles gardés.
     * Les coûts d'achat sont hors taxes : la TVA collectée n'est pas une marge.
     */
    public function grossMargin(): ?float
    {
        $cost = $this->costTotal();

        return $cost === null ? null : round($this->excludingVat($this->netAfterReturns()) - $cost, 2);
    }

    /** Taux de marge brute, en % du net encaissé (avoirs déduits). */
    public function marginRate(): ?float
    {
        $margin = $this->grossMargin();
        $net = $this->excludingVat($this->netAfterReturns());

        return $margin === null || $net <= 0 ? null : round($margin / $net * 100, 1);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    /** Avoirs (retours d'articles), du plus ancien au plus récent. */
    public function returns()
    {
        return $this->hasMany(SaleReturn::class)->oldest()->orderBy('id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Client facultatif (null pour une vente anonyme). */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function toString()
    {
        return $this->reference;
    }
}
