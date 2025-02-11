@extends('layouts.app')

@section('title', __('ClassRecords'))

@section('content')
<div class="container">
  <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-fw fa-book-reader"></i> {{__('ClassRecords')}}</h1>
  
  <div class="show-links">
      <button class="btn btn-light">本页记录数量：{{count($classRecords)}}</button>
  </div>

  <div class="col-md-12 col-sm-12 p-0">
      <div class="table-responsive">
        <table class="table">
            <thead>
              <tr>
              	<th scope="col">Action</th>
                <th scope="col">Student</th>
              	<th scope="col">Teacher</th>
                <th scope="col">Agency</th>
                <th scope="col">ClassAt</th>
              	<th scope="col" class="d-none">exception</th>
                <th scope="col">Flag</th>
              </tr>
            </thead>
            <tbody>
              @foreach($classRecords as $classRecord)
                  <tr id={{$classRecord->id}}>
                    <th  data-label="Actions"  scope="row">
                      <a class="btn btn-sm btn-outline-dark text-uppercase" href="{{ route('classRecords.edit', $classRecord->id) }}">
                        Edit
                      </a>
                      
                      <a class="btn btn-sm btn-{{$classRecord->remark?'success':'warning'}} text-uppercase" href="{{ route('classRecords.show', $classRecord->id) }}">评估</a>
                      <a class="btn btn-sm btn-{{$classRecord->getFirstMedia('mp3')?'success':'warning'}} text-uppercase" href="{{ route('classRecords.show', $classRecord->id) }}">Mp3</a>
                      <a class="btn btn-sm btn-{{$classRecord->getFirstMedia('mp4')?'success':'warning'}} text-uppercase" href="{{ route('classRecords.show', $classRecord->id) }}">Mp4</a>

                    </th>
                    <td data-label="Student">{{$classRecord->user->profiles->first()->name}}</td>
                    <td data-label="Teacher">{{$classRecord->teacher->profiles->first()->name}}</td>
                    <td data-label="Agency">{{$classRecord->agency->profiles->first()->name}}</td>
                    <td data-label="ClassAt">{{$classRecord->generated_at->format('m/d H:i 周N')}}</td>
                    <td data-label="exception" class="exception d-none">{{\App\Models\ClassRecord::EXCEPTION_TYPES[$classRecord->exception]}}
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
