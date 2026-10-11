@extends('layouts.app-back-office')

@section('title', 'Edit Customer : ' . $customer->name)

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('admin.customers.update', $customer->id) }}" method="POST">
                @csrf
                @method('PUT')
                @include('contact::customers._form')
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('admin.customers.show', $customer->id) }}" class="btn btn-outline-secondary">Cancel</a>
            </form>
        </div>
    </div>
@endsection
