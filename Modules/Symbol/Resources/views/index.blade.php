@extends('adminlte::page')

@section('title','Symbols')

@section('content_header')
    <h1>Symbols</h1>
@stop

@section('content')
    <div id="app" data-token="{{$token}}" data-url="{{route('symbol::index')}}"></div>
@endsection

@section('js')
    <script src="{{asset('js/chunk-vendors.72034549.js')}}"></script>
    <script src="{{asset('js/app.37066207.js')}}"></script>
@endsection
