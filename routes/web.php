<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('e',function (){

    dd(//$data
        (new \Modules\Symbol\Exchanges\Huobi(config('symbol.proxies.0')))->getAssets(),
  //      (new \Modules\Symbol\Exchanges\Bybit(config('symbol.proxies.0')))->baseCoinInfo('BTC')
    );
    //Artisan::call(' trader:symbol BTC:USDT 1000000 0');
   // dd((new \Modules\Symbol\Exchanges\Binance(config('symbol.proxies.0')))->symbolData());
});
