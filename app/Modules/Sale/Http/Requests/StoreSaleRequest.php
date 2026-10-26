<?php

namespace App\Modules\Sale\Http\Requests;

use App\Modules\Sale\Models\Sale;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
     * @return array<string, ValidationRule|array<mixed>|string>
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
            // Encaissement : sans montant reçu, le client est réputé avoir payé le montant exact
            'payment_method' => ['nullable', Rule::in(Sale::PAYMENT_METHODS)],
            'amount_received' => 'nullable|numeric|min:0',
            // Mobile Money : opérateur et numéro de transaction obligatoires, pour rapprocher le paiement ;
            // un numéro déjà utilisé chez le même opérateur est refusé (une transaction ne paie qu'une vente)
            'payment_operator' => ['nullable', 'required_if:payment_method,'.Sale::PAYMENT_MOBILE_MONEY, Rule::in(Sale::MOBILE_OPERATORS)],
            'payment_reference' => [
                'nullable', 'required_if:payment_method,'.Sale::PAYMENT_MOBILE_MONEY, 'string', 'max:50',
                Rule::unique('sales', 'payment_reference')->where('payment_operator', $this->input('payment_operator')),
            ],
            'products' => 'required|array|min:1',
            // 'distinct' empêche d'envoyer deux fois le même product_color_id
            'products.*.product_color_id' => 'required|exists:product_colors,id|distinct',
            'products.*.quantity' => 'required|integer|min:1',
        ];
    }

    /** Numéro de transaction sans espaces autour (copié depuis le SMS de l'opérateur). */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('payment_reference'))) {
            $this->merge(['payment_reference' => trim($this->input('payment_reference'))]);
        }
    }

    public function attributes(): array
    {
        return [
            'payment_operator' => __('Mobile Money operator'),
            'payment_reference' => __('Transaction number'),
        ];
    }
}
