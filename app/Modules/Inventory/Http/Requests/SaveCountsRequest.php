<?php

namespace App\Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Saisie des quantités comptées : counts[<id de ligne>] = quantité (vide = non comptée).
 */
class SaveCountsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'counts' => 'array',
            'counts.*' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'counts.*.integer' => 'Les quantités comptées doivent être des nombres entiers.',
            'counts.*.min' => 'Les quantités comptées ne peuvent pas être négatives.',
        ];
    }
}
