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
                $exchanges[$exchange] = [
                    'title'=>config('symbol.exchanges.'.$exchange.'.title'),
                    'enabled'=>in_array($symbol,$sym)
                ];
            }

            $data[] = [
                'label'=>$symbol,
                'exchanges'=>$exchanges,
                'enabled'=>in_array($symbol,$activeSymbols)
            ];
        }

        return response()->json(compact('data'));
    }

    public function store(SymbolRequest $request)
    {
        Symbol::updateOrCreate([
            'name'=>$request->symbol,
            'volume'=>$request->volume
        ]);

        return response()->json(1);
    }

    public function delete(SymbolRequest $request)
    {
        Symbol::whereName($request->symbol)->delete();

        return response()->json(1);
    }
}
