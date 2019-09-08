@extends('layouts.app')

@section('title', $type . __('Orders'))

@section('content')
<div class="container">
	<h1>{{ $type }} {{__('Orders')}}</h1>
  <div class="show-links">
    
  </div>

  <div class="show-links">
      <a href="{{ route('home') }}" class="btn btn-outline-dark mt-1"><i class="fas fa-angle-left fa-large"></i> {{__('Go Back')}}</a>
      <a href="{{ route('orders.create') }}" class="btn btn-warning mt-1">{{__('Create')}}</a>

      @foreach(App\Models\Order::LIST_BY as $item)
        @php
          $route = "orders.{$item}";
          $isActive = url()->current() == route($route);
        @endphp
        <a href="{{ route($route) }}" class="btn btn-{{$isActive?'':'outline-'}}primary mt-1 text-capitalize">{{ $item }} </a>
      @endforeach
      <button class="btn btn-light mt-1">本页记录数量：{{count($orders)}}</button>
      @include('shared.search')
      
  </div>

  <div class="col-md-12 col-sm-12 p-0"> 
      <div class="table-responsive">
        <table class="table">
            <thead>
              <tr>
              	<th scope="col">#</th>
                <th scope="col">Student</th>
              	<th scope="col">Teacher</th>
              	<th scope="col">Agency</th>
                <th scope="col">Price</th>
                <th scope="col">Period</th>
                <th scope="col">已上</th>
                <th scope="col">Status</th>
                <th scope="col">ExpireAt</th>
                <th scope="col">标记动作</th>
              </tr>
            </thead>
            <tbody>
              @foreach($orders as $order)
                  <tr id={{$order->id}}>
                    <td data-label="#">
                      <a href="{{ route('orders.show', $order->id) }}" class="fas fa-calendar-alt fa-lg" alt="上课日历" title="上课日历"></a>


                      <a href="{{route('classRecords.indexbyOrder', $order) }}" class="fas fa-list fa-lg" alt="上课记录" title="上课记录"></a>
                      
                      <a href="{{ route('rrules.create', $order) }}" class="fas fa-calendar-times fa-lg" alt="创建计划" title="创建计划"></a>

                      <a href="{{ route('orders.edit', $order->id) }}"  class="fas fa-edit fa-lg"></a>
                    </td>
                    <td data-label="Student">{{$order->user->profiles->first()->name}}</td>
                    <td data-label="Teacher">{{$order->teacher->profiles->first()->name}}</td>
                    <td data-label="Agency">{{$order->agency->profiles->first()->name}}</td>
                    <td data-label="Price">{{$order->price}}</td>
                    <td data-label="Period">{{$order->period}}</td>
                    <td data-label="已上">{{$order->classDoneRecords()->count()}}</td>
                    <td data-label="Book">{{App\Models\Order::STATUS[$order->status]}}</td>
                    <td data-label="ExpireAt">{{$order->expired_at->format('Y.m.d')}}</td>
                    <td data-label="操作">
                      <a data-exception="0" title="标记作废" class="post-action btn btn-{{$order->status==0?'warning':'outline-danger'}} btn-sm" href="{{ route('orders.flagStatus',[$order->id, 0]) }}">作废</a>
                      <a data-exception="1" title="标记正常" class="post-action btn btn-{{$order->status==1?'success':'outline-danger'}} btn-sm" href="{{ route('orders.flagStatus',[$order->id, 1]) }}">正常</a>
                      <a data-exception="2" title="标记完成" class="post-action btn btn-{{$order->status==2?'warning':'outline-danger'}} btn-sm" href="{{ route('orders.flagStatus',[$order->id, 2]) }}">完成</a>
                      <a data-exception="3" title="标记暂停" class="post-action btn btn-{{$order->status==3?'warning':'outline-danger'}} btn-sm" href="{{ route('orders.flagStatus',[$order->id, 3]) }}">暂停</a>
                      <a data-exception="4" title="标记过期" class="post-action btn btn-{{$order->status==4?'warning':'outline-danger'}} btn-sm" href="{{ route('orders.flagStatus',[$order->id, 4]) }}">过期</a>
                    </td>
                  </tr>
              @endforeach
            </tbody>
          </table>
      </div>
      {{ $orders->onEachSide(1)->links() }}
  </div>
</div>
@endsection

@section('scripts')
<script type="text/javascript">
    window.onload = function () {

        $('.post-action').click(function(e){
          e.preventDefault();
          var msg = "This action cannot be undone, Are you sure to flag?";
          var that = $(this);
          if(!that.hasClass('btn-outline-danger')){
            alert('{{__('NO Action on this status')}}');
            return 0;
          }

          if (confirm(msg)) {
            thisException = that.data('exception');
            thisParent = that.parent('td');

            var actions = that.parent('td');
            // var nextType = that.data('type')=='aol'?'absent':'aol';
            // var next = actions.find('a[data-type='+nextType+']');
            var statusText = that.attr('label');
            var target = actions.parent('tr').find('.exception');
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.ajax({
              url:that.attr('href'),
              type:"POST",
              success: function(data) {
                if(data.success){
                  target.text(statusText);
                  that.removeClass('btn-outline-danger').addClass('btn-warning');
                  if(thisException==1){
                    that.removeClass('btn-warning').addClass('btn-success');
                  }
                  thisParent.find('.post-action').each(function(){
                    thatException = $(this).data('exception');
                    if(thisException != thatException){
                      $(this).removeClass('btn-warning').addClass('btn-outline-danger');
                      if(thatException==1){
                        $(this).removeClass('btn-success');
                      }
                    }
                  })
                  @role('student')
                  actions.text('--');
                  @endrole
                }
              }
            });
          }
        });
    }
</script>
  @include('scripts.search')
@endsection
