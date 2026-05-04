<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $colorCount = is_array($this->colors) ? count($this->colors) : 0;

        return [
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0', // Prix général par défaut

            // Validation des tableaux de variantes
            'colors' => 'required|array|min:1',
            'colors.*' => 'required|string|max:50',

            // On force la taille des tableaux à correspondre à celle de 'colors'
            'stocks' => "required|array|size:{$colorCount}",
            'stocks.*' => 'required|integer|min:0',

            'prices' => "required|array|size:{$colorCount}",
            'prices.*' => 'nullable|numeric|min:0',

            'alert_stocks' => "nullable|array|size:{$colorCount}",
            'alert_stocks.*' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'stocks.size' => 'Le nombre de stocks doit correspondre au nombre de couleurs ajoutées.',
            'prices.size' => 'Le nombre de prix doit correspondre au nombre de couleurs ajoutées.',
            'colors.required' => 'Vous devez ajouter au moins une variante (couleur/stock).',
        ];
    }
}
