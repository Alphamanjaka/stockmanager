<?php

namespace Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = ['product_id', 'sku', 'price', 'attributes'];

    // C'est ce cast qui convertit automatiquement le JSONB Postgres en Tableau PHP
    protected $casts = [
        'attributes' => 'array',
    ];
}
