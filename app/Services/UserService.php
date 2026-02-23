<?php

namespace App\Services;

use App\Models\User;
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
    public function login(array $credentials)
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw new \Exception('Les identifiants fournis sont incorrects.');
        }

        if (isset($credentials['role']) && $user->role !== $credentials['role']) {
            throw new \Exception('Vous n\'avez pas accès à ce profil.');
        }

        return $user;
    }
    public function logout()
    {
        auth()->logout();
    }
    public function update($id, $data)
    {
        $user = $this->getUserById($id);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);
        return $user->fresh();
    }
    public function delete($id)
    {
        $user = $this->getUserById($id);
        return $user->delete();
    }
    public function getAllUsers()
    {
        return User::latest()->paginate(15);
    }
    public function getUserById($id)
    {
        $user = User::find($id);
        if (!$user) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Utilisateur non trouvé.');
        }
        return $user;
    }
    public function register(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
        ]);

        return $user;
    }
}
