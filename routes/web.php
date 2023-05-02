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

Route::get('ex',function (){/*
    $tgBot = new \Telegram\Bot\Api(env('TELEGRAM_BOT_TOKEN'));
    $tgBot->sendMessage([
        'chat_id'=>env('TELEGRAM_CHAT_ID'),
        'text'=>'Mexc -> ByBit | XTZ/USDT
📉Покупка:
Объем: 15074.72 USDT -> 13129 XTZ
Цена: 1.14766-1.14879$
📈Продажа:
Объем: 13129 XTZ -> 16783.44 USDT
Цена: 1.358-1.2052$
Профит: 1708.72 USDT
Спред: 11.34%
📤Вывод:
✅ Mexc | ✅ Bybit
'
    ]);*/
});
