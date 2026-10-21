@extends('layouts.app-back-office')

@section('title', __('New purchase'))
@section('subtitle', __('Purchase order: choose the supplier, add the lines, then save, order or receive.'))

@section('content')
    @include('purchase::purchases._form')
@endsection
