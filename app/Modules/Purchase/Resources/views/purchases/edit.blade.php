@extends('layouts.app-back-office')

@section('title', 'Edit purchase')
@section('subtitle', $purchase->reference . ' · draft')

@section('content')
    @include('purchase::purchases._form')
@endsection
