<?php

namespace App\Modules\Catalog\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Adjust authorization logic as needed
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "name"=> "required|string|max:255|unique:categories,name",
            "description"=> "nullable|string|min:10",
            "parent_id"=> "nullable|exists:categories,id",
            'option_template' => 'nullable|array|max:5',
            'option_template.*.name' => ['nullable', 'string', 'max:50', 'regex:/^[^\[\]]*$/'],
            'option_template.*.values' => 'nullable|string|max:1000',
        ];
    }
}
