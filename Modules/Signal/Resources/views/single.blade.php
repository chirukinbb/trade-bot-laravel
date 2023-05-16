<?php
/**
 * @var \Modules\Signal\Entities\Signal $signal
 */
?>@extends('adminlte::page')

@section('title','Signal')

@section('content_header')
    <h1>Signal</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Signal for {{$signal->base_coin}}:{{$signal->quote_coin}}</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-6">
                    <h2>Buy at {{$signal->buy_exchange}}</h2>
                    <p>prices: {{implode(' - ',$signal->buy_prices)}} {{$signal->base_coin}}</p>
                </div>
                <div class="col-6">
                    <h2>Sell at {{$signal->sell_exchange}}</h2>
                    <p>prices: {{implode(' - ',$signal->sell_prices)}} {{$signal->base_coin}}</p>
                </div>
                <div class="col-12">
                    Volumes:
                    <p>sell {{$signal->buy_volumes[0]}} {{$signal->base_coin}}  for buy {{$signal->buy_volumes[1]}} {{$signal->quote_coin}}</p>
                    <p>sell {{$signal->buy_volumes[1]}} {{$signal->quote_coin}} for buy {{$signal->buy_volumes[0]}} {{$signal->base_coin}} </p>
                    Spread: {{$signal->spread()}}
                    Profit: {{$signal->profit()}}
                </div>
            </div>
        </div>
    </div>
    @if($signal->deals()->count())
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Deals for signal</h3>
        </div>
        <div class="card-body">
            <div class="row">
                @foreach($orders as $order)
                <div class="col-6">
                    <h2>{{$order['side']}} at {{$order['exchange']}}</h2>
                    <p>Volume: {{$order['volume']}} {{$signal->base_coin}}</p>
                    <p>Price: {{$order['price']}}</p>
                    <p>Status: {{$order['status']}}</p>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
@endsection
