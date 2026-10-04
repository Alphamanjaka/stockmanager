@extends('layouts.app-back-office')

@section('title', 'Customer : ' . $customer->name)
@section('actions')
    <a href="{{ route('admin.sales.index', ['customer' => $customer->id]) }}" class="btn btn-outline-primary">
        <i class="fas fa-shopping-cart"></i> Sales history
    </a>
    <a href="{{ route('admin.customers.edit', $customer->id) }}" class="btn btn-primary">
        <i class="fas fa-edit"></i> Edit
    </a>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">{{ $customer->name }}</h5>
                    <dl class="row mb-0">
                        <dt class="col-sm-3">Phone</dt>
                        <dd class="col-sm-9">{{ $customer->phone ?: '—' }}</dd>
                        <dt class="col-sm-3">Email</dt>
                        <dd class="col-sm-9">{{ $customer->email ?: '—' }}</dd>
                        <dt class="col-sm-3">Address</dt>
                        <dd class="col-sm-9">{{ $customer->address ?: '—' }}</dd>
                        <dt class="col-sm-3">Customer since</dt>
                        <dd class="col-sm-9">{{ $customer->created_at->format('d/m/Y') }}</dd>
                    </dl>
                </div>
            </div>
            @if ($customer->notes)
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white fw-bold">Notes</div>
                    <div class="card-body" style="white-space: pre-line">{{ $customer->notes }}</div>
                </div>
            @endif
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-danger">
                <div class="card-body">
                    <p class="small text-muted mb-2">Deleting a customer keeps their sales (they become anonymous).</p>
                    <form action="{{ route('admin.customers.destroy', $customer->id) }}" method="POST"
                        onsubmit="return confirm('Delete this customer?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100"><i class="fas fa-trash"></i> Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
