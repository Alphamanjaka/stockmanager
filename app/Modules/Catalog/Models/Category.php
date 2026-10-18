<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use App\Modules\Catalog\Database\Factories\CategoryFactory;
use App\Core\Models\Concerns\Archivable;

#[UseFactory(CategoryFactory::class)]
class Category extends Model
{
    use HasFactory, Archivable;
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

    /**
     * Une catégorie archivée disparaît des listes de choix : elle ne doit plus contenir de produit
     * ni de sous-catégorie en service (ils deviendraient introuvables par catégorie).
     */
    public function archiveBlocker(): ?string
    {
        if ($count = $this->products()->active()->count()) {
            return "elle contient {$count} produit(s) actif(s) : archivez-les ou déplacez-les d'abord";
        }
        if ($count = $this->children()->active()->count()) {
            return "elle a {$count} sous-catégorie(s) active(s)";
        }

        return null;
    }
}
