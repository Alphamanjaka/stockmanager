<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;  // Uncomment if you want to use factories for Category model
    //
    protected $fillable = [
        'name',
        'description',
        'parent_id',
    ];
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
