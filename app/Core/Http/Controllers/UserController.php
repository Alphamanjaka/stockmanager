<?php

namespace App\Core\Http\Controllers;

use App\Core\Http\Controllers\Concerns\ArchivesRecords;
use App\Core\Http\Requests\StoreUserRequest;
use App\Core\Http\Requests\UpdateUserRequest;
use App\Core\Services\UserService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ArchivesRecords;

    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index(Request $request)
    {
        $users = $this->userService->getAllUsers($request->only(['search', 'role', 'status']));

        return view('core::users.index', compact('users'));
    }

    public function create()
    {
        return view('core::users.create');
    }

    public function store(StoreUserRequest $request)
    {
        $this->userService->create($request->validated());

        // Correction de la route de redirection
        return redirect()->route('admin.users.index')
            ->with('success', 'Utilisateur créé avec succès.');
    }

    public function edit(User $user)
    {
        return view('core::users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        // On passe directement l'objet User au service
        $this->userService->update($user, $request->validated());

        return redirect()->route('admin.users.index')
            ->with('success', 'Utilisateur mis à jour avec succès.');
    }

    /**
     * Suppression définitive d'un compte archivé qui n'a jamais vendu ni compté de stock :
     * sinon la base refuse, le compte reste archivé et ses ventes gardent son nom.
     * (Son propre compte ne peut pas être archivé, donc pas supprimé non plus.)
     */
    public function destroy(User $user)
    {
        return $this->deletePermanently($user->id, 'admin.users.index');
    }

    /** Archiver un compte le désactive : il ne peut plus se connecter (LogoutArchivedUser, AuthController). */
    protected function archiveTargets(array $ids): Builder
    {
        return User::whereKey($ids);
    }
}
