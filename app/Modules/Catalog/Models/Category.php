<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use App\Modules\Catalog\Database\Factories\CategoryFactory;

#[UseFactory(CategoryFactory::class)]
class Category extends Model
{
    use HasFactory;  // Uncomment if you want to use factories for Category model
    //
    protected $fillable = [
        'name',
        'description',
        'parent_id',
        'option_template',
    ];

    /**
     * option_template : options proposées à la création d'un produit de cette catégorie
     * (ex. Postiches → [{"name":"Couleur","values":[]},{"name":"Taille","values":[]}]).
     */
    protected function casts(): array
    {
        return [
            'option_template' => 'array',
        ];
    }
    // obtain the parent category: $cat->parent
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }
    // obtain all children categories: $cat->children
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
