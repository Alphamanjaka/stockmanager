<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreSaleRequest;
use App\Models\ProductColor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreSaleRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper pour valider des données contre les règles du StoreSaleRequest.
     */
    protected function validate(array $data)
    {
        $request = new StoreSaleRequest();
        return Validator::make($data, $request->rules());
    }

    public function test_authorize_returns_true()
    {
        $request = new StoreSaleRequest();
        $this->assertTrue($request->authorize());
    }

    public function test_validation_passes_with_valid_data()
    {
        // On crée une variante en base pour satisfaire la règle 'exists'
        $variant = ProductColor::factory()->create();

        $data = [
            'discount' => 10.5,
            'products' => [
                [
                    'product_color_id' => $variant->id,
                    'quantity' => 2
                ]
            ]
        ];

        $validator = $this->validate($data);
        $this->assertTrue($validator->passes(), "La validation devrait passer avec des données correctes.");
    }

    public function test_validation_fails_if_discount_is_negative()
    {
        $data = ['discount' => -1, 'products' => []];
        $validator = $this->validate($data);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('discount', $validator->errors()->toArray());
    }

    public function test_validation_fails_if_products_is_empty()
    {
        $data = ['products' => []];
        $validator = $this->validate($data);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('products', $validator->errors()->toArray());
    }

    public function test_validation_fails_if_product_color_id_is_duplicated()
    {
        $variant = ProductColor::factory()->create();

        // On envoie deux fois le même ID pour tester la règle 'distinct'
        $data = [
            'products' => [
                ['product_color_id' => $variant->id, 'quantity' => 1],
                ['product_color_id' => $variant->id, 'quantity' => 5],
            ]
        ];

        $validator = $this->validate($data);

        $this->assertFalse($validator->passes(), "La règle 'distinct' devrait invalider les IDs en double.");
        $this->assertArrayHasKey('products.0.product_color_id', $validator->errors()->toArray());
    }

    public function test_validation_fails_if_quantity_is_less_than_one()
    {
        $data = [
            'products' => [
                ['product_color_id' => 1, 'quantity' => 0]
            ]
        ];

        $validator = $this->validate($data);
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('products.0.quantity', $validator->errors()->toArray());
    }
}
