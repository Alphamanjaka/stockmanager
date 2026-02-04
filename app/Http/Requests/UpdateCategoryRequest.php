<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // On récupère l'ID de la catégorie depuis l'URL (route binding)
        // Si votre route est defined comme /categories/{category}, le paramètre est 'category'
        // Si c'est /categories/{id}, le paramètre est 'id' ou 'category' selon le binding.
        // Dans un resource controller standard, c'est souvent le nom du modèle au singulier.
        $categoryId = $this->route('category');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('categories')->ignore($categoryId)],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:categories,id'],
        ];
    }
}
