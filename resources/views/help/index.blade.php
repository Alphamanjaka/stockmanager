@extends('layouts.app-back-office')
@section('title', 'Help & Support')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Help & Support</h5>
                </div>
                <div class="card-body">
                    <p>Welcome to the Help & Support section of StockManager! Here you can find resources and guides to
                        assist you in using the application effectively.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('styles')
    <style>
        /* Custom styles for the help page */
        .card-header {
            background-color: #17a2b8;
        }
    </style>
@endsection

@push('scripts')
    <script>
        // Custom JavaScript for the help page (if needed)
        console.log('Help page loaded');
    </script>
@endpush
