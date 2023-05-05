@extends('adminlte::page')

@section('title','Settings')

@section('content_header')
    <h1>Settings</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Settings</h3>
        </div>
        <form class="card-body" method="post">
            @csrf
            <div class="mb-3">
                <label for="exampleFormControlInput1" class="form-label">Time Interval</label>
                <input type="number" name="delay" class="form-control" id="exampleFormControlInput1" value="{{env('SECONDS_DELAY')}}">
            </div>
            <div class="mb-3">
                <label for="exampleFormControlTextarea1" class="form-label">Target Spread</label>
                <input type="number" name="spread" class="form-control" id="exampleFormControlInput1" value="{{env('TARGET_SPREAD')}}">
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
        </form>
    </div>
@endsection
