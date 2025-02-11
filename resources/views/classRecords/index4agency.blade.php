@extends('layouts.app')

@section('title', __('ClassRecords'))

@section('content')
<div class="container">
  <div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-fw fa-share-alt"></i> {{__('ClassRecords')}}</h1>
    <button class="btn btn-light">本页记录数量：{{count($classRecords)}}</button>
  </div>
  
  <div class="col-md-12 col-sm-12 p-0">
      <div class="table-responsive">
        <table class="table">
            <thead>
              <tr>
                <th scope="col">学生</th>
                <th scope="col">上课时间</th>
              	<th scope="col">课程状态</th>
                <th>#</th>
              </tr>
            </thead>
            <tbody>
              @foreach($classRecords as $classRecord)
                  <tr id="{{$classRecord->id}}">
                    <td data-label="学生">{{$classRecord->user->profiles->first()->name}}</td>
                    <td data-label="上课时间">{{$classRecord->generated_at->format('m/d H:i 周N')}}</td>
                    <td data-label="课程状态">{{\App\Models\ClassRecord::EXCEPTION_TYPES[$classRecord->exception]}}
                    </td>

                    <th scope="row">
                      <a class="btn btn-sm text-uppercase btn-outline-dark" href="{{ route('classRecords.show', $classRecord->id) }}">查看</a>
                    </th>
                  </tr>
              @endforeach
            </tbody>
        </table>
      </div>
      {{ $classRecords->onEachSide(1)->links() }}
  </div>
</div>
@endsection
