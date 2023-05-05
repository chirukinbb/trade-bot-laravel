<?php

namespace Modules\Quotation\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Quotation\Entities\Signal;
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

        return view('quotation::index',compact(
            'signals',
            'symbols'
        ));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('quotation::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('quotation::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('quotation::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }
}
