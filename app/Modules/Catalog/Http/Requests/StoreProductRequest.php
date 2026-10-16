<?php

namespace App\Modules\Catalog\Http\Requests;

use App\Modules\Catalog\Support\VariantOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Création d'un produit avec ses options et sa grille de variantes.
 *
 * options[i][name|values]           axes de variation (valeurs séparées par des virgules)
 * variants[i][enabled]              variante retenue (case cochée)
 * variants[i][values][<option>]     combinaison de la variante
 * variants[i][price|price_purchase|alert_stock|brand_id]
 * specs[i][key|value]               informations libres du produit
 *
 * Pas de stock : les variantes démarrent à 0, le stock n'évolue que par des mouvements (achat reçu, vente...).
 */
class StoreProductRequest extends FormRequest
{
    public const MAX_VARIANTS = 200;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',

            'options' => 'nullable|array|max:5',
            // Pas de crochets : le nom sert de clé dans les champs du formulaire (variants[i][values][<nom>])
            'options.*.name' => ['nullable', 'string', 'max:50', 'regex:/^[^\[\]]*$/'],
            'options.*.values' => 'nullable|string|max:1000',

            'specs' => 'nullable|array|max:30',
            'specs.*.key' => 'nullable|string|max:100',
            'specs.*.value' => 'nullable|string|max:1000',

            'variants' => 'required|array|min:1|max:' . self::MAX_VARIANTS,
            'variants.*.enabled' => 'nullable|boolean',
            'variants.*.values' => 'nullable|array',
            'variants.*.values.*' => 'nullable|string|max:100',
            'variants.*.price' => 'nullable|numeric|min:0',
            'variants.*.price_purchase' => 'nullable|numeric|min:0',
            'variants.*.alert_stock' => 'nullable|integer|min:0',
            'variants.*.brand_id' => 'nullable|exists:brands,id',
        ];
    }

    /**
     * Cohérence de la grille : au moins une variante retenue, chaque combinaison complète et conforme
     * aux options déclarées, et pas deux fois la même combinaison (même marque).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $options = VariantOptions::normalizeOptions($this->input('options', []));
            $enabled = array_filter($this->input('variants', []), fn ($variant) => !empty($variant['enabled']));

            if ($enabled === []) {
                $validator->errors()->add('variants', 'Select at least one variant.');

                return;
            }

            $signatures = [];
            foreach ($enabled as $variant) {
                $values = $variant['values'] ?? [];
                foreach ($options as $option) {
                    $value = trim((string) ($values[$option['name']] ?? ''));
                    if ($value === '' || ($option['values'] !== [] && !in_array($value, $option['values'], true))) {
                        $validator->errors()->add('variants', "Each variant needs a valid value for « {$option['name']} ».");

                        return;
                    }
                }

                $brandId = !empty($variant['brand_id']) ? (int) $variant['brand_id'] : null;
                $signature = VariantOptions::signature(VariantOptions::combination($options, $values), $brandId);
                if (in_array($signature, $signatures, true)) {
                    $validator->errors()->add('variants', 'The same variant appears twice.');

                    return;
                }
                $signatures[] = $signature;
            }
        });
    }

    public function messages(): array
    {
        return [
            'variants.required' => 'Add at least one variant.',
            'variants.max' => 'Too many variants (maximum ' . self::MAX_VARIANTS . '): reduce the number of option values.',
        ];
    }
}
