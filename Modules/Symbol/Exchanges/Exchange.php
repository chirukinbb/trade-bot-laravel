<?php

namespace Modules\Symbol\Exchanges;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

abstract class Exchange
{
    protected string $name = 'exchange';
    protected PendingRequest $http;

    public function __construct(array $proxy)
    {
        $this->http = Http::withOptions([
            'proxy'=>[
                'https' => "https://{$proxy['user']}:{$proxy['pass']}@{$proxy['address']}:{$proxy['port']}"
            ]
        ]);
    }

    abstract public function symbols(): array;
    abstract public function isSymbolOnline(string $symbol): bool;
    abstract public function orderBook(string $symbol): array;
    abstract public function sendOrder(array $data): array;

    protected function normalize(string $symbol):string
    {
        $symbol = str_replace(':',config('symbol.exchanges.'.$this->name.'.separator'),$symbol).config('symbol.exchanges.'.$this->name.'.suffix');

        return  config('symbol.exchanges.'.$this->name.'.lowercase') ? strtolower($symbol) : $symbol;
    }

    public function link(string $symbol)
    {
        $link = config('symbol.exchanges.'.$this->name.'.link');

        return str_replace('{symbol}',$this->normalize($symbol),$link);
    }

    protected function extractBook(array $data)
    {
        $book = [];
        $i = 0;
        $count = min(count($data['asks']),count($data['bids']));

        while ($i < $count){
            $book['asks'][] = [
                'price'=>$data['asks'][$i][0],
                'value'=>$data['asks'][$i][1],
            ];
            $book['bids'][] = [
                'price'=>$data['bids'][$i][0],
                'value'=>$data['bids'][$i][1],
            ];

            $i++;
        }

        return $book;
    }
}
