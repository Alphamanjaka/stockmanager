@extends('layouts.app-back-office')

@section('title', '- Settings')

@section('content')
    <div class="container">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row">
            <!-- Sidebar Navigation (Vertical Tabs) -->
            <div class="col-md-3 mb-4">
                <div class="nav flex-column nav-pills me-3" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                    <button class="nav-link active text-start" id="v-pills-general-tab" data-bs-toggle="pill"
                        data-bs-target="#v-pills-general" type="button" role="tab" aria-controls="v-pills-general"
                        aria-selected="true">
                        <i class="fas fa-building me-2"></i> Company
                    </button>
                    <button class="nav-link text-start" id="v-pills-regional-tab" data-bs-toggle="pill"
                        data-bs-target="#v-pills-regional" type="button" role="tab" aria-controls="v-pills-regional"
                        aria-selected="false">
                        <i class="fas fa-globe me-2"></i> Regional & Currency
                    </button>
                    <button class="nav-link text-start" id="v-pills-stock-tab" data-bs-toggle="pill"
                        data-bs-target="#v-pills-stock" type="button" role="tab" aria-controls="v-pills-stock"
                        aria-selected="false">
                        <i class="fas fa-boxes me-2"></i> Stock & Products
                    </button>
                    <button class="nav-link text-start" id="v-pills-interface-tab" data-bs-toggle="pill"
                        data-bs-target="#v-pills-interface" type="button" role="tab" aria-controls="v-pills-interface"
                        aria-selected="false">
                        <i class="fas fa-sliders-h me-2"></i> Interface & System
                    </button>
                </div>

                <div class="mt-4 d-grid">
                    <button type="submit" class="btn btn-primary" form="settings-form">
                        <i class="fas fa-save me-2"></i> Save all
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div class="col-md-9">
                <form id="settings-form" action="{{ route('admin.settings.update', ['setting' => 1]) }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="tab-content" id="v-pills-tabContent">
                        <div class="tab-pane fade show active" id="v-pills-general" role="tabpanel"
                            aria-labelledby="v-pills-general-tab">
                            @include('core::settings.partials.general')
                        </div>

                        <div class="tab-pane fade" id="v-pills-regional" role="tabpanel"
                            aria-labelledby="v-pills-regional-tab">
                            @include('core::settings.partials.regional')
                        </div>

                        <div class="tab-pane fade" id="v-pills-stock" role="tabpanel" aria-labelledby="v-pills-stock-tab">
                            @include('core::settings.partials.stock_products')
                        </div>

                        <div class="tab-pane fade" id="v-pills-interface" role="tabpanel"
                            aria-labelledby="v-pills-interface-tab">
                            @include('core::settings.partials.interface')
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endsection

