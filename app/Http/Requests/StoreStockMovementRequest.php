<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreStockMovementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // validation d'une entrée ou sortie de stock
            'product_color_id' => 'required|exists:product_colors,id',
            'quantity' => 'required|integer|min:1',
            'type' => 'required|in:in,out',
            'reason' => 'nullable|string|max:255',
        ];
    }
}
