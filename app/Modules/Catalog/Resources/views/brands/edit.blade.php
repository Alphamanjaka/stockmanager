@extends('layouts.app-back-office')

@section('title', 'Edit Brand : ' . $brand->name)

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('admin.brands.update', $brand->id) }}" method="POST">
                @csrf
                @method('PUT')
                @include('catalog::brands._form')
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('admin.brands.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </form>
        </div>
    </div>
@endsection
