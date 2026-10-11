@extends('layouts.app-back-office')

@section('title', 'Add Brand')

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('admin.brands.store') }}" method="POST">
                @csrf
                @include('catalog::brands._form')
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('admin.brands.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </form>
        </div>
    </div>
@endsection
