<?php

namespace App\Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Ouverture d'un inventaire (l'accès back office est garanti par les routes du module).
 */
class StoreInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'nullable|exists:categories,id',
            'notes' => 'nullable|string|max:2000',
        ];
    }
}
