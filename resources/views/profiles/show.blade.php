@extends('layouts.app')
@section('title', __('Profile'))
@section('content')
<div class="container">
  <h1>{{$profile->name}}</h1>
    <div class="row justify-content-center">
        <div class="col-md-12 col-sm-12 p-0">
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Name</th>
                  <th>Sex</th>
                  <th>Birthday</th>
                  <th>Telephone</th>
                  <th>Recommender</th>
                </tr>
              </thead>
              <tbody>
                <tr id={{$profile->id}}>
                  <th scope="row" data-label="Id">
                    <a href="{{ route('profiles.edit', $profile->id) }}" class="btn btn-sm btn-outline-dark text-uppercase">Edit</a>
                  </th> 
                  <td data-label="Name">{{$profile->name}}</td>
                  <td data-label="Sex">{{$profile->sex}}</td>
                  <td data-label="Birthday">{{$profile->birthday?$profile->birthday->format('m/d'):'-'}}</td>
                  <td data-label="Telephone">{{$profile->telephone}}</td>
                  <td data-label="Recommender">{{$profile->recommend_uid}}</td>
                </tr>
              </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
