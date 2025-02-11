@extends('layouts.app')

@section('title', __('ClassRecords'))

@section('content')
<div class="container">
  <h1 class="h3 pb-2 text-gray-800"><i class="fas fa-fw fa-book-reader"></i> {{__('ClassRecords')}}</h1>
  
  <div class="show-links">
      <a href="{{ route('orders.index') }}" class="btn btn-outline-dark"><i class="fas fa-angle-left fa-large"></i> {{__('Go Back')}}</a>
      <a href="{{route('orders.show', $order) }}" class="btn btn-outline-dark">View in Calander</a>
      <button class="btn btn-light">本页记录数量：{{count($classRecords)}}</button>
  </div>

  @can('Update any Order')
  <div class="mt-3 mb-2">
    {!! form($form) !!}
  </div>
  <div class="table-responsive">
    <table class="table">
        <thead>
          <tr>
              <th scope="col">总课时</th>
              <th scope="col">已上(含今天)</th>
              <th scope="col">老师请假</th>
              <th scope="col">学生请假</th>
              <th scope="col">旷课作废</th>
              <th scope="col">老师异常</th>
              <th scope="col">教材</th>
          </tr>
        </thead>
        <tbody>
              <tr id={{$order->id}}>
                <td data-label="Period">{{$order->period}}</td>
                <td data-label="已上(含今天)">{{$order->classDoneRecords()->count()}}</td>
                <td data-label="老师请假">{{$order->classRecordsAolBy('teacher')->count()}}</td>
                <td data-label="学生请假">{{$order->classRecordsAolBy('student')->count()}}</td>
                <td data-label="旷课作废">{{$order->classRecordsAolBy('absent')->count()}}</td>
                <td data-label="老师异常">{{$order->classRecordsAolBy('exception')->count()}}</td>
                <td data-label="教材">{{$order->book->publisher}} | {{$order->book->name}}</td>
              </tr>
        </tbody>
      </table>
  </div>
  @endcan
  
  <div class="col-md-12 col-sm-12 p-0">
      <div class="table-responsive">
        <table class="table">
            <thead>
              <tr>
                <th scope="col">id</th>
                <th scope="col">#</th>
                <th scope="col">Student</th>
                <th scope="col">Teacher</th>
                <th scope="col">Agency</th>
                <th scope="col">Class Time</th>
                <th scope="col" class="d-none">exception</th>
                <th scope="col">Flag</th>
              </tr>
            </thead>
            <tbody>
              @foreach($classRecords as $key => $classRecord)
                  <tr id="{{$classRecord->id}}">
                    <th scope="row">
                      @can('Update any ClassRecord')
                      <a class="btn btn-sm btn-outline-dark text-uppercase" href="{{ route('classRecords.edit', $classRecord->id) }}">
                        Edit
                      </a>
                      @else
                      {{$classRecord->id}}
                      @endcan
                    </th>
                    <td scope="row" data-label="Status">
                      @if(!$classRecord->remark && $classRecord->generated_at->isToday())
                        <a  target="_blank" class="btn btn-sm btn-success text-uppercase" href="https://zhumu.me/j/{{ $classRecord->teacher->teacher->pmi }}">Zoom</a>
                      @endif

                      <a class="btn btn-sm btn-{{$classRecord->remark?'success':'warning'}} text-uppercase" href="{{ route('classRecords.show', $classRecord->id) }}">评估</a>
                      <a class="btn btn-sm btn-{{$classRecord->getFirstMedia('mp3')?'success':'warning'}} text-uppercase" href="{{ route('classRecords.show', $classRecord->id) }}">Mp3</a>
                      <a class="btn btn-sm btn-{{$classRecord->getFirstMedia('mp4')?'success':'warning'}} text-uppercase" href="{{ route('classRecords.show', $classRecord->id) }}">Mp4</a>
                      @can('cut', $classRecord)
                        <a href="{{ route('videos.cut', $classRecord->id) }}" class="btn btn-sm btn-outline-info">Cut</a>
                      @endcan
                    </td>
                    <td data-label="Student">
                      {{$classRecord->user->profiles->first()->name}}
                      @if($classRecord->generated_at->isToday())
                      <a class="btn btn-sm btn-outline-dark btn-confirm" data-confirm="确定发短信给学生吗？" href="{{route('admin.classNotifyStudent', $classRecord->id) }}"><i class="fas fa-sms fa-large"></i></a>
                      @endif
                    </td>
                    <td data-label="老师">
                      {{$classRecord->teacher->profiles->first()->name}}
                      @if($classRecord->generated_at->isToday())
                      <a class="btn btn-sm btn-outline-dark btn-confirm" data-confirm="确定发短信给老师吗？" href="{{route('admin.classNotifyTeacher', $classRecord->id) }}"><i class="fab fa-telegram fa-large"></i></a>
                      @endif
                    </td>
                    <td data-label="agency">{{$classRecord->agency->profiles->first()->name}}</td>
                    <td data-label="ClassAt">{{$classRecord->generated_at->format('m/d H:i 周N')}}</td>
                    <td data-label="exception"  class="exception d-none">{{\App\Models\ClassRecord::EXCEPTION_TYPES[$classRecord->exception]}}
                    </td>
                    <td data-label="Flag">
                      <a  data-type="aol" data-exception="1" label="AOL" title="Click to AOL" class="post-action btn btn-{{$classRecord->exception==1?'warning':'outline-danger'}} btn-sm" href="{{ route('classRecords.flagException',[$classRecord->id, 1]) }}">AOL</a>
                      <a data-type="absent" data-exception="3" label="Absent" title="Click to Absent" class="post-action btn btn-{{$classRecord->exception==3?'warning':'outline-danger'}} btn-sm" href="{{ route('classRecords.flagException',[$classRecord->id, 3]) }}">Absent</a>

                      <a data-type="aol2" data-exception="2" label="老师请假" title="Click to Teacher AOL" class="post-action btn btn-{{$classRecord->exception==2?'warning':'outline-danger'}} btn-sm" href="{{ route('classRecords.flagException',[$classRecord->id, 2]) }}">老师请假</a>

                      <a data-type="aol4" data-exception="4" label="老师异常" title="Click to Teacher AOL" class="post-action btn btn-{{$classRecord->exception==4?'warning':'outline-danger'}} btn-sm" href="{{ route('classRecords.flagException',[$classRecord->id, 4]) }}">老师异常</a>
                      <a data-type="aol0" data-exception="0" label="正常" title="Click to Teacher AOL" class="post-action btn btn-{{$classRecord->exception==0?'success':'outline-danger'}} btn-sm" href="{{ route('classRecords.flagException',[$classRecord->id, 0]) }}">正常</a>
                    </td>
                  </tr>
              @endforeach
            </tbody>
        </table>
      </div>
      {{ $classRecords->onEachSide(1)->links() }}
  </div>
</div>
@endsection

@section('scripts')
  @include('classRecords.aol-script')
@endsection
