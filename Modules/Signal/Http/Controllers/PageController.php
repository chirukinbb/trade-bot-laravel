<?php

namespace Modules\Signal\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Signal\Entities\Deal;
use Modules\Signal\Entities\Signal;
use Modules\Symbol\Http\Requests\SymbolRequest;

class PageController extends Controller
{

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        $symbols = [];

        Signal::select(\DB::raw('concat(`base_coin`,":",`quote_coin`) as name'))
        ->groupBy(\DB::raw('concat(`base_coin`,":",`quote_coin`)'))
            ->each(function (Signal $signal) use (&$symbols){
            $symbols[] = $signal->name;
        });

        $signals = Signal::whereIn(
            \DB::raw('concat(`base_coin`,":",`quote_coin`)'),
            (is_null($request->symbol) || $request->symbol === 'all') ? $symbols : [$request->symbol]
        )->paginate();

        return view('Signal::index',compact(
            'signals',
            'symbols'
        ));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function single(int $id)
    {
        $signal = Signal::find($id);
        $orders = [];

        $signal->deals->each(function (Deal $deal)use (&$orders){
            $className = config('symbol.exchanges.'.$deal->exchange.'.adapter');
            $orders[] = array_merge(
                (new $className)->order($deal->exchange_id),
                ['exchange'=>config('symbol.exchanges.'.$deal->exchange.'.title')]
            );
        });

        return view('Signal::single',compact(
            'signal',
        'orders'
        ));
    }
}
