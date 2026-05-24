<?php

namespace App\Services;

use App\Models\Product;

class ProductService extends BaseService
{
    public function __construct(private Product $product)
    {
        parent::__construct($product);
    }

    public function create(array $data)
    {
        if (isset($data['name'])) {
            $data['name'] = mb_strtolower(trim($data['name']));
        }
        return parent::create($data);
    }

    public function update(int $id, array $data)
    {
        if (isset($data['name'])) {
            $data['name'] = mb_strtolower(trim($data['name']));
        }
        return parent::update($id, $data);
    }
}
