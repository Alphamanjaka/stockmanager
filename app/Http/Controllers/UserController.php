<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index()
    {
        $users = $this->userService->getAllUsers();
        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(StoreUserRequest $request)
    {
        $this->userService->create($request->validated());

        // Correction de la route de redirection
        return redirect()->route('admin.users.index')
            ->with('success', 'Utilisateur créé avec succès.');
    }

    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        // On passe directement l'objet User au service
        $this->userService->update($user, $request->validated());

        return redirect()->route('admin.users.index')
            ->with('success', 'Utilisateur mis à jour avec succès.');
    }

    public function destroy(User $user)
    {
        // Empêcher la suppression de son propre compte
        if (auth()->id() === $user->id) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        try {
            $this->userService->delete($user->id);
            return redirect()->route('admin.users.index')
                ->with('success', 'Utilisateur supprimé avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la suppression de l\'utilisateur : ' . $e->getMessage());
        }
    }
}
