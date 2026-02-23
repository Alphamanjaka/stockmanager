@extends('layouts.app-back-office')

@section('title', 'Suppliers')

@section('content')
    <a href="{{ route('admin.suppliers.create') }}" class="btn btn-primary mb-3"><i class="bi bi-plus-circle"></i> Add Supplier</a>
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form action="{{ url('admin/suppliers') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Rechercher un fournisseur..."
                        value="{{ request('search') }}">
                </div>

                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filter</button>
                    <a href="{{ url('admin/suppliers') }}" class="btn btn-outline-secondary" rel="noopener">Reset</a>
                </div>
            </form>
        </div>
    </div>
    <div class="container">
        <h1>List of Suppliers</h1>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($suppliers as $supplier)
                    <tr>
                        <td>{{ $supplier->id }}</td>
                        <td>{{ $supplier->name }}</td>
                        <td>{{ $supplier->email }}</td>
                        <td>{{ $supplier->phone }}</td>
                        <td>{{ $supplier->address }}</td>
                        <td>
                            <a href="{{ route('admin.suppliers.show', $supplier->id) }}"
                                class="btn btn-info btn-sm">Voir</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-4 d-flex justify-content-center">
            {{ $suppliers->links() }}
        </div>
    </div>
@endsection
