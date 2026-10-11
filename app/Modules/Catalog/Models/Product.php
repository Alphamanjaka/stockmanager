<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use App\Modules\Catalog\Database\Factories\ProductFactory;

#[UseFactory(ProductFactory::class)]
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
        'options',
        'specs',
    ];

    /**
     * options : axes de variation ([{"name":"Contenance","values":["25 ml","30 ml"]}])
     * specs   : informations libres du produit ({"Composition":"...","Origine":"Chine"})
     */
    protected function casts(): array
    {
        return [
            'options' => 'array',
            'specs' => 'array',
        ];
    }

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
