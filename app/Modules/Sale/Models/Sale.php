<?php

namespace App\Modules\Sale\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Modules\Contact\Models\Customer;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use App\Modules\Sale\Database\Factories\SaleFactory;

#[UseFactory(SaleFactory::class)]
class Sale extends Model
{
    use HasFactory;

    /** Modes de paiement acceptés en caisse (libellés : lang/xx/pos.php, clé methods). */
    public const PAYMENT_CASH = 'cash';
    public const PAYMENT_METHODS = [self::PAYMENT_CASH, 'mobile_money', 'card'];

    protected $fillable = ['reference', 'total_brut', 'discount', 'total_net','user_id', 'customer_id', 'company_name', 'company_email', 'company_phone', 'company_address','currency_symbol', 'payment_method', 'amount_received'];

    /** Monnaie rendue au client : somme reçue moins le net à payer (0 si la somme reçue n'est pas connue). */
    public function getChangeDueAttribute(): float
    {
        return $this->amount_received === null ? 0.0 : max(0, round($this->amount_received - $this->total_net, 2));
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
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
