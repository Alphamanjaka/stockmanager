<?php

namespace App\Modules\Catalog\Services;

use App\Core\Services\BaseService;
use App\Modules\Catalog\Models\Product;

class ProductService extends BaseService
{
    protected array $sortable = ['id', 'name', 'price', 'created_at'];

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
