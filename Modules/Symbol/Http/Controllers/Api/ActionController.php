<?php

namespace Modules\Symbol\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Lin\Binance\Binance;
use Lin\Bybit\BybitSpot;
use Lin\Gate\GateSpotV2;
use Lin\Ku\Kucoin;
use Lin\Mxc\MxcSpot;
use Modules\Symbol\Entities\Symbol;
use Modules\Symbol\Http\Requests\SymbolRequest;

class ActionController extends Controller
{
    public function symbols()
    {
        $symbols = [];
        $activeSymbols = [];
        $data = [];
        $pivotSymbols = [];

        foreach (config('symbol.exchanges') as $exchange  => $data) {
            $symbols[$exchange] = call_user_func([new $data['adapter'],'symbols']);

            foreach ($symbols[$exchange] as $symbol) {
                if (!in_array($symbol,$pivotSymbols)){
                    $pivotSymbols[] = $symbol;
                }
            }
        }

        Symbol::each(function (Symbol $symbol) use (&$activeSymbols){
            $activeSymbols[] = $symbol->name;
        });

        foreach ($pivotSymbols as $symbol){
            $exchanges = [];

            foreach ($symbols as $exchange => $sym) {
                $exchanges[$exchange] = in_array($symbol,$sym);
            }

            $data[] = [
                'label'=>$symbol,
                'exchanges'=>$exchanges,
                'enabled'=>in_array($symbol,$activeSymbols)
            ];
        }

        return response()->json($data);
    }

    public function store(SymbolRequest $request)
    {
        Symbol::updateOrCreate(['name'=>$request->symbol]);

        return response()->json(1);
    }

    public function delete(SymbolRequest $request)
    {
        Symbol::whereName($request->symbol)->delete();

        return response()->json(1);
    }

    private function getGateSymbols()
    {
        $gate = new GateSpotV2();

        return array_map(function ($symbol) {
            return str_replace('_',':',strtoupper($symbol));
        }, $gate->publics()->pairs());
    }

    private function getMexcSymbols()
    {
        $mxc  = new MxcSpot();

        return array_map(function ($symbol) {
            return str_replace('_',':',$symbol['symbol']);
        }, $mxc->market()->getSymbols()['data']);
    }

    private function getOKXSymbols()
    {
        $symbols = json_decode(\Illuminate\Support\Facades\Http::get('https://www.okx.com/api/v5/public/instruments?instType=SPOT')->body());

        return array_map(function ($symbol) {
            $symbol = (array)$symbol;
            return $symbol['baseCcy'].':'.$symbol['quoteCcy'];
        }, (array)$symbols->data);
    }

    private function getKucoinSymbols()
    {
        $kucoin  = new Kucoin();

        return array_map(function ($symbol) {
            return $symbol['baseCurrency'].':'.$symbol['quoteCurrency'];
        }, $kucoin->market()->getSymbols()['data']);
    }

    private function getBybitSymbols()
    {
        $bybit = new BybitSpot();

        return array_map(function ($symbol) {
            return $symbol['baseCurrency'].':'.$symbol['quoteCurrency'];
        }, $bybit->publics()->getSymbols()['result']);
    }

    private function getHuobiSymbols()
    {
        $huobiSymbols = json_decode(\Illuminate\Support\Facades\Http::get('https://api.huobi.pro/v1/common/symbols')->body());

        return array_map(function ($symbol) {
            $symbol = (array)$symbol;
            return strtoupper($symbol['base-currency'].':'.$symbol['quote-currency']);
        }, $huobiSymbols->data);
    }

    private function getBinanceSymbols()
    {
        $binance = new Binance();

        return array_map(function ($symbol) {
            return $symbol['baseAsset'].':'.$symbol['quoteAsset'];
        }, $binance->system()->getExchangeInfo()['symbols']);
    }
}
