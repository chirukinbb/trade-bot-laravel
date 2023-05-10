<?php

namespace Modules\Symbol\Exchanges;

use Lin\Bybit\BybitSpot;

class Bybit extends Exchange
{
    protected object $sdk;
    protected string $name = 'bybit';

    public function __construct()
    {
        $this->sdk = new BybitSpot(env('BYBIT_API_KEY',''),env('BYBIT_API_SECRET',''));
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
        $data = $this->sdk->publics()->getDepth(['symbol'=>$this->normalize($symbol),'limit'=>5])['result'];
        $book = [];
        $i = 0;

        while ($i < count($data['asks'])){
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

    public function link(string $symbol)
    {
        $link = config('symbol.exchanges.'.$this->name.'.link');

        return str_replace('{symbol}',str_replace(':','/',$symbol),$link);
    }

    public function sendOrder(array $data): array
    {
        return [];
    }
}
