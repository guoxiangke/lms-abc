@extends('layouts.app')

@section('title', 'Create Order')

@section('content')
<div class="container">
	<h1 class="h3 mb-0 text-gray-800"><i class="fas fa-fw fa-cart-plus"></i>Create Order</h1>
	<br>

    <div class="row justify-content-center">
        <div class="col-md-12 col-sm-12"> 
            {!! form($form) !!}
        </div>
    </div>
</div>
@endsection

@include('layouts.chosen')
