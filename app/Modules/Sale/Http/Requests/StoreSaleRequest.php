<?php

namespace App\Modules\Sale\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // authorise all users for this project
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    // app/Http/Requests/StoreSaleRequest.php
    public function rules(): array
    {
        return [
            'discount' => 'nullable|numeric|min:0',
            // Client facultatif : existant, ou nouveau (nom + téléphone saisis dans le formulaire de vente)
            'customer_id' => 'nullable|exists:customers,id',
            'new_customer_name' => 'nullable|string|max:255',
            'new_customer_phone' => 'nullable|string|max:50',
            'products' => 'required|array|min:1',
            // 'distinct' empêche d'envoyer deux fois le même product_color_id
            'products.*.product_color_id' => 'required|exists:product_colors,id|distinct',
            'products.*.quantity'   => 'required|integer|min:1',
        ];
}
}
