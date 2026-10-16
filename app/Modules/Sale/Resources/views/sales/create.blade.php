@extends('layouts.app-front-office')

@section('title', __('pos.title'))

@section('content')
    @include('sale::partials.pos', ['storeUrl' => route('saler.store')])
@endsection
