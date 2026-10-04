@extends('layouts.app-back-office')

@section('title', 'Add Customer')

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('admin.customers.store') }}" method="POST">
                @csrf
                @include('contact::customers._form')
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </form>
        </div>
    </div>
@endsection
