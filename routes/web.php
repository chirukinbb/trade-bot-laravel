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
    //dd(4);
    dd((new \Modules\Symbol\Exchanges\OKX(config('symbol.proxies.0')))->isSymbolOnline('BTC:USDT'));
});
