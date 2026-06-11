@extends('layouts.app-back-office') {{-- Adaptez à votre layout principal --}}
@section('title', 'User Management')
@section('actions')
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
        <i class="fa fa-plus"></i> Create User
    </a>
@endsection
@section('content')


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
            {{-- recherche --}}
            <form action="{{ url('admin/users') }}" method="GET" class="row align-items-center">
                @csrf
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control" placeholder="Rechercher un utilisateur..."
                        value="{{ request('search') }}">
                </div>
                {{--  filtre d'utilisateurs: vendeur et administrateur --}}
                <div class="col-md-2">
                    <select name="role" class="form-select" aria-label="Default select example">
                        <option value="vendeur">vendeur</option>
                        <option value="administrateur">administrateur</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Filter</button>
                    <a href="{{ url('admin/users') }}" class="btn btn-outline-secondary" rel="noopener">Reset</a>
                </div>
            </form>
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
                                    <span class="badge {{ $user->isBackOffice() ? 'bg-success' : 'bg-info text-dark' }}">
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
@endsection
