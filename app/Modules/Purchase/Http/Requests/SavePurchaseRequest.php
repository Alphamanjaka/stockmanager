<?php

namespace App\Modules\Purchase\Http\Requests;

use App\Modules\Purchase\Services\PurchaseService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Bon de commande (création et modification d'un brouillon).
 *
 * supplier_id, supplier_reference, notes   en-tête
 * products[i][product_color_id|quantity|unit_price]   lignes (une variante au plus une fois)
 * discount                                 remise sur le total, jamais supérieure au total brut
 * action                                   draft (par défaut), ordered ou received (réception : entrée en stock)
 */
class SavePurchaseRequest extends FormRequest
{
    public const MAX_LINES = 500;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => 'required|exists:suppliers,id',
            'supplier_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:2000',
            'discount' => 'nullable|numeric|min:0',
            'products' => 'required|array|min:1|max:'.self::MAX_LINES,
            'products.*.product_color_id' => 'required|distinct|exists:product_colors,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.unit_price' => 'required|numeric|min:0',
            'action' => ['nullable', Rule::in(array_keys(PurchaseService::ACTIONS))],
        ];
    }

    /** La remise ne peut pas dépasser le total des lignes. */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $total = collect($this->input('products', []))
                ->sum(fn ($line) => (float) ($line['quantity'] ?? 0) * (float) ($line['unit_price'] ?? 0));

            if ((float) $this->input('discount', 0) > $total) {
                $validator->errors()->add('discount', 'La remise ne peut pas dépasser le total des lignes.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'supplier_id.required' => 'Choisissez le fournisseur.',
            'products.required' => 'Ajoutez au moins une ligne.',
            'products.min' => 'Ajoutez au moins une ligne.',
            'products.*.product_color_id.distinct' => 'Un même produit apparaît sur deux lignes.',
            'products.*.quantity.min' => 'La quantité doit être d\'au moins 1.',
        ];
    }

    /** Action demandée par le bouton d'envoi (brouillon par défaut). */
    public function action(): string
    {
        return $this->validated('action') ?? 'draft';
    }
}
