<?php

namespace App\Core\Http\Requests;

use App\Core\Support\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Seuls les administrateurs gèrent les comptes (un gérant n'a pas accès aux utilisateurs)
        return auth()->user() && auth()->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
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
            'role' => ['required', 'string', Rule::in(Role::ALL)],
        ];
    }
}
