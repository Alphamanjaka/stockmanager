@extends('layouts.app-back-office')

@section('title', 'New purchase')
@section('subtitle', 'Purchase order: choose the supplier, add the lines, then save, order or receive.')

@section('content')
    @include('purchase::purchases._form')
@endsection
