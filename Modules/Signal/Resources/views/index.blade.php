<?php
/**
 * @var \Modules\Signal\Entities\Signal $signal
 */
?>@extends('adminlte::page')

@section('title','Signals')

@section('content_header')
    <h1>Signals</h1>
    <style>
        tr{
            cursor: pointer;
        }
    </style>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Signal Table</h3>
            <div class="card-tools">
                <form class="input-group">
                    <select name="symbol" class="form-control" onchange="$(this).closest('form').submit()" id="">
                        <option value="all">All Symbols</option>
                        @foreach($symbols as $symbol)
                            <option value="{{$symbol}}" @selected(request('symbol') === $symbol)>{{$symbol}}</option>
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
                    <th>Profit</th>
                    <th>Buy</th>
                    <th>Sell</th>
                    <th>Trades</th>
                    <th>DateTime</th>
                </tr>
                </thead>
                <tbody>
                @foreach($signals as $signal)
                    <tr onclick="window.location = '{{route('signal::single',['id'=>$signal->id])}}'">
                        <td>{{$signal->base_coin.':'.$signal->quote_coin}}</td>
                        <td>
                            <span class="text-success d-block">{{$signal->profit()}}</span>
                            {{$signal->spread()}}
                        </td>
                        <td>
                            <span class="text-primary d-block">{{implode(' - ',$signal->buy_prices)}}</span>
                            {{$signal->buy_exchange}}
                        </td>
                        <td>
                            <span class="text-danger d-block">{{implode(' - ',$signal->sell_prices)}}</span>
                            {{$signal->sell_exchange}}
                        </td>
                        <td>
                            @if($signal->deals()->count())
                                <span class="text-success">Yes</span>
                            @else
                                <span class="text-secondary">No</span>
                            @endif
                        </td>
                        <td>{{$signal->created_at}}</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>{{$signals->links()}}</tfoot>
            </table>
        </div>
    </div>
@endsection
