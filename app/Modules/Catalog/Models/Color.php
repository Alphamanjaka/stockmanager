<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use App\Modules\Catalog\Database\Factories\ColorFactory;

#[UseFactory(ColorFactory::class)]
class Color extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code'];

    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', "%{$search}%");
    }


    public function products()
    {
        return $this->belongsToMany(Product::class);
    }
}
