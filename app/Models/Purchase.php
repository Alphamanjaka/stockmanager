<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
