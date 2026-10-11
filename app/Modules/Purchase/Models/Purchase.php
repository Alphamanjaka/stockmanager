<?php

namespace App\Modules\Purchase\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Contact\Models\Supplier;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use App\Modules\Purchase\Database\Factories\PurchaseFactory;

#[UseFactory(PurchaseFactory::class)]
class   Purchase extends Model
{
    use HasFactory;
    // define fillable fields
    protected $fillable = [
        'reference',
        'total_amount',
        'discount',
        'total_net',
        'supplier_id',
        'state',
    ];

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
