@extends('layouts.app-back-office')

@section('title', __('Edit purchase'))
@section('subtitle', $purchase->reference . ' · ' . __('draft'))

@section('content')
    @include('purchase::purchases._form')
@endsection
