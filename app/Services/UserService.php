<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{

    public function create(array $data)
    {
        throw new \Exception("");
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
        throw new \Exception("");
    }
    public function update($id, $data)
    {
        throw new \Exception("");
    }
    public function delete($id)
    {
        throw new \Exception("");
    }
    public function getAllUsers()
    {
        throw new \Exception("");
    }
    public function getUserById($id)
    {
        throw new \Exception("");
    }
    public function register(array $data)
    {
        // let's create the user
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
        ]);

        return $user;
    }
}
