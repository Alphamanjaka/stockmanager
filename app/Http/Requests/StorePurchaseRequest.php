<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user() && auth()->user()->isBackOffice();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        // Validation rules for creating a purchase
        // supplier_id is optional but if provided, it must exist in the suppliers table
        // products is required and must be an array with at least one item
        // each item in products must have a product_id that exists in the product_color table,
        //  a quantity that is an integer greater than 0, and a unit_price that is a numeric value greater
        // than or equal to 0
        return [
            'supplier_id' => 'exists:suppliers,id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:product_colors,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.unit_price' => 'required|numeric|min:0',
        ];
    }
    public function messages(): array
    {
        return [
            'supplier_id.exists' => 'Le fournisseur sélectionné est invalide.',
            'products.required' => 'Vous devez ajouter au moins un produit à l\'achat.',
            'products.array' => 'Le format des produits est invalide.',
            'products.min' => 'Vous devez ajouter au moins un produit à l\'achat.',
            'products.*.product_id.required' => 'Chaque produit doit être sélectionné.',
            'products.*.product_id.exists' => 'Le produit sélectionné est invalide.',
            'products.*.quantity.required' => 'La quantité est requise pour chaque produit.',
            'products.*.quantity.integer' => 'La quantité doit être un nombre entier.',
            'products.*.quantity.min' => 'La quantité doit être au moins de 1.',
            'products.*.unit_price.required' => 'Le prix unitaire est requis pour chaque produit.',
            'products.*.unit_price.numeric' => 'Le prix unitaire doit être un nombre.',
            'products.*.unit_price.min' => 'Le prix unitaire doit être au moins de 0.',
        ];
    }
}