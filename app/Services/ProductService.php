<?php

namespace App\Services;

use App\Models\Product;

class ProductService extends BaseService
{
    public function __construct(private Product $product)
    {
        parent::__construct($product);
    }
}