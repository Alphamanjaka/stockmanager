@extends('layouts.app-back-office')

@section('title', __('pos.title'))

@section('content')
    @include('sale::partials.pos', ['storeUrl' => route('admin.sales.store')])
@endsection
