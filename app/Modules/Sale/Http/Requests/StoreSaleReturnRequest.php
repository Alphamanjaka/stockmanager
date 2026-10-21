<?php

namespace App\Modules\Sale\Http\Requests;

use App\Modules\Sale\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Avoir : quantities[<id de ligne de vente>] = quantité rendue (vide ou 0 = rien), ou all=1 pour tout rendre
 * (annulation de la vente). Le motif est obligatoire. Les quantités maximales sont vérifiées par le service.
 */
class StoreSaleReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => 'required|string|min:3|max:500',
            'refund_method' => ['nullable', Rule::in(Sale::PAYMENT_METHODS)],
            'all' => 'nullable|boolean',
            'quantities' => 'array',
            'quantities.*' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Indiquez le motif du retour.',
            'reason.min' => 'Le motif du retour est trop court.',
            'quantities.*.integer' => 'Les quantités rendues doivent être des nombres entiers.',
            'quantities.*.min' => 'Les quantités rendues ne peuvent pas être négatives.',
        ];
    }
}
