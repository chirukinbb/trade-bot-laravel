@extends('adminlte::page')

@section('title','Dashboard')

@section('content_header')
    <h1>Dashboard</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Stats Table</h3>
            <div class="card-tools">
                <form class="input-group">
                    <select name="period" class="form-control" onchange="$(this).closest('form').submit()">
                        @foreach($periods as $key=>$period)
                            <option value="{{$key}}" @selected(request('period') == $key)>{{$period}}</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th>Symbol</th>
                    <th>Count</th>
                    <th>Avg Volume</th>
                    <th>Max Volume</th>
                    <th>Sum Profit</th>
                    <th>Max Profit</th>
                </tr>
                </thead>
                <tbody>
                @foreach($stats as $symbol => $stat)
                <tr>
                    <td>{{$symbol}}</td>
                    <td>{{$stat['count']}}</td>
                    <td>{{$stat['volume']['avg']}}</td>
                    <td>{{$stat['volume']['max']}}</td>
                    <td>{{$stat['profit']['sum']}}</td>
                    <td>{{$stat['profit']['max']}}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
