<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Autoriser tout le monde pour ce projet
    }

    public function rules(): array
    {
        // Récupération de l'ID du produit si on est en mode mise à jour (route param 'product' ou 'id')
        $product = $this->route('product') ?? $this->route('id');
        $productId = $product instanceof \App\Models\Product ? $product->id : $product;

        return [
            'name'           => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'name')->ignore($productId)
            ],
            'category_id'    => 'required|exists:categories,id',
            'price'          => 'required|numeric|min:0',
            'quantity_stock' => 'required|integer|min:0',
            'alert_stock'    => 'nullable|integer|min:0',
            'description'    => 'nullable|string',
        ];
    }

    // Optionnel : Personnaliser les messages d'erreur
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du produit est indispensable !',
            'name.unique'   => 'Ce nom de produit existe déjà en stock.',
            'price.numeric' => 'Le prix doit être un nombre valide.',
            'category_id.required' => 'Veuillez sélectionner une catégorie.',
        ];
    }
}
