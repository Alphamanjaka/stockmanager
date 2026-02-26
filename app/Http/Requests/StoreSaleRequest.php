<?php

namespace App\Http\Requests;

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
            'products' => 'required|array|min:1',
            // 'distinct' empêche d'envoyer deux fois le même product_id
            'products.*.product_id' => 'required|exists:products,id|distinct',
            'products.*.quantity'   => 'required|integer|min:1',
        ];
    }
}
