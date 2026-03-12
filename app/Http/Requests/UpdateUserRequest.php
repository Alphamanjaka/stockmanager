<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Seuls les utilisateurs avec le rôle 'back_office' peuvent mettre à jour un profil.
        return auth()->user() && auth()->user()->isBackOffice();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'name' => 'required|string|max:255',
            // Ignore l'utilisateur actuel lors de la vérification de l'unicité de l'email.
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            // Le mot de passe est optionnel, mais s'il est fourni, il doit être confirmé et avoir 8 caractères min.
            'password' => 'nullable|string|min:8|confirmed',
            'role' => ['required', 'string', Rule::in(['front_office', 'back_office'])],
        ];
    }
}
