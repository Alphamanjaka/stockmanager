@extends('layouts.app-back-office')

@section('content')
    <h1>Liste des Mouvements de Stock</h1>
    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>REfecence</th>
                        <th>Product</th>
                        <th>Type Movement</th>
                        <th>Quantity</th>
                        <th>Stock before</th>
                        <th>Stock after</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($stockMovements as $movement)
                        <tr>
                            <td>{{ $movement->reason }}</td>
                            <td>{{ $movement->product->name }}</td>
                            <td>{{ $movement->type }}</td>
                            <td>{{ $movement->quantity }}</td>
                            <td>{{ $movement->stock_before }}</td>
                            <td>{{ $movement->stock_after }}</td>
                            <td>{{ $movement->created_at }}</td>
                            <td>
                                <a href="{{ route('admin.movements.show', $movement->id) }}" class="btn btn-primary">Show</a>
                            </td>
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
