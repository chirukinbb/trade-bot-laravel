<?php

use Illuminate\Support\Facades\Route;
use Lin\Binance\Binance;
use Lin\Bitget\BitgetSpot;
use Lin\Bybit\BybitSpot;
use Lin\Gate\GateSpot;
use Lin\Huobi\HuobiSpot;
use Lin\Ku\Kucoin;
use Lin\Mxc\MxcSpot;
use Lin\Okex\OkexSpot;
use Modules\Symbol\Entities\Symbol;
use Modules\Symbol\Exchanges\Exchange;
use Modules\Trader\Entities\Trade;

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
    Artisan::call('trader:symbol BTC:USDT 100');
});
