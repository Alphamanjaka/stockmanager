<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\Factories\HasFactory;

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
    public function products()
    {
        return $this->hasMany(Product::class);
    }
    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

}
