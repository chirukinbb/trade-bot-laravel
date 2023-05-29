<?php

namespace Modules\Symbol\Repositories;

use Modules\Symbol\Entities\Symbol;

class SymbolRepository
{
    private Symbol $symbol;

    public function __construct()
    {
        $this->symbol = Symbol::getModel();
    }

    public function fromExchanges()
    {
        $symbols = [];
        $pivotSymbols = [];

        foreach (config('symbol.exchanges') as $exchange  => $data) {
            $symbols[$exchange] = call_user_func([new $data['adapter'],'symbols']);

            foreach ($symbols[$exchange] as $symbol) {
                if (!in_array($symbol,$pivotSymbols)){
                    $pivotSymbols[] = $symbol;
                }
            }
        }

        return compact('symbols','pivotSymbols');
    }

    public function allNames()
    {
        $symbols = [];

        $this->symbol->clone()->each(function (Symbol $symbol) use (&$symbols){
            $symbols[] = $symbol->name;
        });

        return $symbols;
    }
}
