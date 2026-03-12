@extends('layouts.app-back-office') {{-- Adaptez à votre layout principal --}}

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Gestion des Utilisateurs</h1>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                <i class="fa fa-plus"></i> Créer un utilisateur
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Liste des utilisateurs</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                        <thead class="table-light">
                            <tr>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th class="text-center">Ventes effectuées</th>
                                <th>Date de création</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
                                <tr>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        <span
                                            class="badge {{ $user->isBackOffice() ? 'bg-success' : 'bg-info text-dark' }}">
                                            {{ $user->isBackOffice() ? 'Administrateur' : 'Vendeur' }}
                                        </span>
                                    </td>
                                    <td class="text-center">{{ $user->sales_count }}</td>
                                    <td>{{ $user->created_at->format('d/m/Y') }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-warning"
                                            title="Modifier">
                                            <i class="fa fa-pencil-alt"></i>
                                        </a>
                                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Supprimer"
                                                {{ auth()->id() === $user->id ? 'disabled' : '' }}>
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">Aucun utilisateur trouvé.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-center">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
