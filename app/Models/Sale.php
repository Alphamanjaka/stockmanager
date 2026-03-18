<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sale extends Model
{
    use HasFactory;
    protected $fillable = ['reference', 'total_brut', 'discount', 'total_net','user_id'];

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }
}
