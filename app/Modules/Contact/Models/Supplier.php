<?php

namespace App\Modules\Contact\Models;

use Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\Purchase\Models\Purchase;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use App\Modules\Contact\Database\Factories\SupplierFactory;

#[UseFactory(SupplierFactory::class)]
class Supplier extends Model
{
    use HasFactory;  // Uncomment if you want to use factories for Supplier model
    //
    protected $table = "suppliers";
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
    ];
    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

}
