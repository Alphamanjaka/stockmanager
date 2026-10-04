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
    protected $fillable = ['reference', 'total_brut', 'discount', 'total_net','user_id', 'customer_id', 'company_name', 'company_email', 'company_phone', 'company_address','currency_symbol'];

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
