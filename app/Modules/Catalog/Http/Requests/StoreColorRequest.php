<?php

namespace App\Modules\Catalog\Http\Requests;

use App\Modules\Catalog\Models\Color;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreColorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $color = $this->route('color') ?? $this->route('id');
        $colorId = $color instanceof Color ? $color->id : $color;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('colors', 'name')->ignore($colorId),
            ],
            'code' => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de la couleur est obligatoire.',
            'name.unique' => 'Cette couleur existe déjà.',
        ];
    }
}
