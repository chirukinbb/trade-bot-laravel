@extends('adminlte::page')
<?php
use Modules\Settings\Entities\Setting;
?>
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
            @foreach($fields as $field=>$data)
                @if(isset($data['title']))
                    <div class="section">
                        <h3>{{$data['title']}}</h3>
                            <div class="row">
                                @foreach($data['fields'] as $key => $title)
                                    <label for="{{$field}}" class="p-2 d-block col-6">
                                        {{$title}}
                                        <input type="text" name="{{$key}}" value="{{Setting::env($key)}}" class="form-control">
                                    </label>
                                @endforeach
                            </div>
                    </div>
                @elseif($data[1] === 'input')
                    <label for="{{$field}}" class="py-2 d-block">
                        {{$data[0]}}
                        <input type="text" name="{{$field}}" value="{{Setting::env($field)}}" class="form-control onlyDigits">
                    </label>
                @else
                    <label for="{{$field}}" class="py-2 d-block">
                        <input type="hidden" name="{{$field}}" value="0">
                        <input type="checkbox" id="{{$field}}" name="{{$field}}" value="1" class="" @checked(Setting::env('IS_TRADING_ENABLED') == 1)>
                        {{$data[0]}}
                    </label>
                @endif
            @endforeach
            <button type="submit" class="btn btn-primary">Save</button>
        </form>
    </div>
@endsection

@section('js')
    <script>
        $('.onlyDigits').on('input', function(){
            this.value = this.value.replace(/[^\d\.,]/g, "");
            this.value = this.value.replace(/,/g, ".");
            if(this.value.match(/\./g).length > 1) {
                this.value = this.value.substr(0, this.value.lastIndexOf("."));
            }
        });
    </script>
@endsection
