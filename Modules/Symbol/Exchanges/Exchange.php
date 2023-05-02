<?php

namespace Modules\Symbol\Exchanges;

abstract class Exchange
{
    protected string $name = 'exchange';

    abstract public function symbols(): array;
    abstract public function isSymbolOnline(string $symbol): bool;
    abstract public function orderBook(string $symbol): array;
    abstract public function sendOrder(string $symbol,float $lot, bool $isSell): array;

    protected function normalize(string $symbol):string
    {
        $symbol = str_replace(':',config('symbol.exchanges.'.$this->name.'.separator'),$symbol).config('symbol.exchanges.'.$this->name.'.suffix');

        return  config('symbol.exchanges.'.$this->name.'.lowercase') ? strtolower($symbol) : $symbol;
    }
}
