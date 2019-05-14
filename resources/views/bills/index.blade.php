@extends('layouts.app')

@section('title', __('Bills'))

@section('content')
<div class="container">
	<h1>{{__('Bills')}}</h1>
  <div class="show-links">
    <a href="{{ route('home') }}" class="btn btn-outline-dark"><i class="fas fa-angle-left fa-large"></i> {{__('Go Back')}}</a>
    <a href="{{ route('bills.create') }}" class="btn btn-outline-primary">{{__('Create')}}</a>
  </div>
    <div class="col-md-12 col-sm-12"> 
        <div class="table-responsive">
          <table class="table">
              <thead>
                <tr>
                	<th scope="col">#</th>
                	<th scope="col">type</th>
                	<th scope="col">user</th>
                	<th scope="col">order</th>
                	<th scope="col">price</th>
                  <th scope="col">paymethod</th>
                  <th scope="col">status</th>
                  <th scope="col">remark</th>
                </tr>
              </thead>
              <tbody>
                @foreach($bills as $bill)
                    <tr id={{$bill->id}}>
                      <th scope="row"><a href="{{ route('bills.edit', $bill->id) }}" class="btn btn-sm btn-outline-dark text-uppercase">Edit</a></th>
                      <td data-label="type">{{App\Models\Bill::TYPES[$bill->type]}}</td>
                      <td data-label="user">{{$bill->user->profiles->first()->name}}</td>
                      <td data-label="order">{{$bill->order?$bill->order->title:'-'}}</td>
                      <td data-label="price">{{$bill->price}}</td>
                      <td data-label="paymethod">{{App\Models\PayMethod::TYPES[$bill->paymethod_type]}}</td>
                      <td data-label="status">{{App\Models\Bill::STATUS[$bill->status]}}</td>
                      <td data-label="remark">{{$bill->remark}}</td>
                    </tr>
                @endforeach
              </tbody>
          </table>
        </div>
        {{ $bills->onEachSide(1)->links() }}
    </div>
</div>
@endsection
