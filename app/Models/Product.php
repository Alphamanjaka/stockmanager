<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    // has factory
    use HasFactory;


    // Define fillable attributes for mass assignment
    protected $fillable = [
        'name',
        'description',
        'price',
        'category_id',
        'quantity_stock',
    ];
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }
    public function scopeFilter($query, $filter) {
        $query->where('name', 'like', "%$filter%");
        
    }
}
