<?php

namespace App\Core\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'] ?? 'front_office',
        ]);
    }

    public function update(User $user, array $data)
    {
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']); // Ne pas mettre à jour le mot de passe s'il est vide
        }

        $user->update($data);

        return $user->fresh();
    }

    public function delete($id)
    {
        $user = $this->getUserById($id); // Utilise findOrFail

        return $user->delete();
    }

    /**
     * @param  array{search?: ?string, role?: ?string, status?: ?string}  $filters  recherche (nom ou e-mail),
     *                                                                              rôle (front_office, back_office), statut (comptes actifs, ou archivés : status=archived)
     */
    public function getAllUsers(array $filters = [])
    {
        return $this->listQuery($filters)->paginate(15)->withQueryString();
    }

    /** Liste des utilisateurs (page et export). */
    public function listQuery(array $filters = []): Builder
    {
        // Ajout de withCount pour compter les ventes de chaque utilisateur de manière optimisée
        return User::withCount('sales')
            ->withStatus($filters['status'] ?? null)
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($q) => $q
                ->whereLike('name', "%{$search}%")
                ->orWhereLike('email', "%{$search}%")))
            ->when(in_array($filters['role'] ?? null, ['front_office', 'back_office'], true),
                fn ($query) => $query->where('role', $filters['role']))
            ->latest('id');
    }

    public function getUserById($id)
    {
        // Utilisation de findOrFail pour un code plus propre et standard
        return User::findOrFail($id);
    }
}
