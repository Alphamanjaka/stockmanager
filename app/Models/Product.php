<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Category;
use App\Models\Color;
use App\Models\StockMovement;

class Product extends Model
{
    // has factory
    use HasFactory;


    // Define fillable attributes for mass assignment
    protected $fillable = [
        'name',
        'description',
        'category_id',
        'price',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Accès direct aux variantes (SKUs)
     */
    public function productColors()
    {
        return $this->hasMany(ProductColor::class);
    }

    public function stockMovements()
    {
        return $this->hasManyThrough(StockMovement::class, ProductColor::class);
    }

    public function colors()
    {
        return $this->belongsToMany(Color::class)
            ->withPivot('stock', 'alert_stock') // Now pivot also tracks alert_stock
            ->timestamps();
    }
    public function toString()
    {
        return $this->name;
    }

    // funciton scopeSearch
    public function scopeSearch($query, $term)
    {
        $term = strtolower($term);
        return $query->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"]);
    }
}