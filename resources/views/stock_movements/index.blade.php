@extends('layouts.app-back-office')

@section('content')
    <h1>Liste des Mouvements de Stock</h1>
    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>Type Movement</th>
                        <th>Quantity</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($stockMovements as $movement)
                        <tr>
                            <td>{{ $movement->id }}</td>
                            <td>{{ $movement->product->name }}</td>
                            <td>{{ $movement->type }}</td>
                            <td>{{ $movement->quantity }}</td>
                            <td>{{ $movement->created_at }}</td>
                        </tr>
                    @endforeach



                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $stockMovements->links() }}
        </div>
    </div>
@endsection
