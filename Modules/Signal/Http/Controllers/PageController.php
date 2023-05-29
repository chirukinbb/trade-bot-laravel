<?php

namespace Modules\Signal\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Signal\Entities\Deal;
use Modules\Signal\Entities\Signal;
use Modules\Signal\Http\Requests\SignalRequest;
use Modules\Signal\Repositories\SignalRepository;
use Modules\Symbol\Http\Requests\SymbolRequest;

class PageController extends Controller
{

    public function __construct(private SignalRepository $signalRepository)
    {
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(SignalRequest $request)
    {
        $symbols = $this->signalRepository->symbols();
        $signals = $this->signalRepository->paginator($request->symbol);

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
        $signal = $this->signalRepository->get($id);
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
