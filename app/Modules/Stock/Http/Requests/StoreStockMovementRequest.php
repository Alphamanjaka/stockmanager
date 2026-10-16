<?php

namespace App\Modules\Stock\Http\Requests;

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
            // Correction manuelle : ajout ou retrait, motif obligatoire (il reste dans l'historique)
            'product_color_id' => 'required|exists:product_colors,id',
            'quantity' => 'required|integer|min:1',
            'type' => 'required|in:in,out',
            'reason' => 'required|string|min:5|max:240',
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Indiquez le motif de la correction (casse, erreur de comptage, retour...).',
            'reason.min' => 'Le motif de la correction doit être plus précis.',
        ];
    }
}
