<?php

namespace Modules\Symbol\Exchanges;

use Lin\Bybit\BybitSpot;

class Bybit extends Exchange
{
    protected object $sdk;
    protected string $name = 'bybit';

    public function __construct()
    {
        $this->sdk = new BybitSpot(/*env('BYBIT_API_KEY'),env('BYBIT_API_SECRET')*/);
    }

    public function symbols(): array
    {
        return array_map(function ($symbol) {
            return $symbol['baseCurrency'].':'.$symbol['quoteCurrency'];
        }, $this->sdk->publics()->getSymbols()['result']);
    }

    public function isSymbolOnline(string $symbol): bool
    {
        $symbolData = array_filter($this->sdk->publics()->getSymbols()['result'],function ($data) use ($symbol){
            return $data['name'] === $this->normalize($symbol);
        });

        return !empty($symbolData) && end($symbolData)['showStatus'];
    }

    public function orderBook(string $symbol): array
    {
        $data = $this->sdk->publics()->getDepth(['symbol'=>$this->normalize($symbol),'limit'=>1])['result'];

        return [
            'ask'=>[
                'price'=>$data['asks'][0][0],
                'value'=>$data['asks'][0][1],
            ],
            'bid'=>[
                'price'=>$data['bids'][0][0],
                'value'=>$data['bids'][0][1],
            ],
        ];
    }

    public function sendOrder(string $symbol, float $lot, bool $isSell): array
    {
        // TODO: Implement sendOrder() method.
    }
}
