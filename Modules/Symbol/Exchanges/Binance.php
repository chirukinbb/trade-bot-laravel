<?php

namespace Modules\Symbol\Exchanges;

use Binance\API;

class Binance extends Exchange
{
    protected object $sdk;
    protected string $name = 'binance';

    public function __construct()
    {
        $this->sdk = new API(env('BINANCE_API_KEY'),env('BINANCE_API_SECRET'));
    }

    public function symbols(): array
    {
        return array_map(function ($symbol) {
            return $symbol['baseAsset'].':'.$symbol['quoteAsset'];
        }, $this->sdk->exchangeInfo()['symbols']);
    }

    public function isSymbolOnline(string $symbol): bool
    {
        $symbolData = array_filter($this->sdk->exchangeInfo()['symbols'],function ($data) use ($symbol){
            return $data['symbol'] === $this->normalize($symbol);
        });

        return !empty($symbolData) && end($symbolData)['status'] === 'TRADING';
    }

    public function orderBook(string $symbol): array
    {
        $data = $this->sdk->depth($this->normalize($symbol),5);
        $book = [];
        $i = 0;

        while ($i < count($data['asks'])){
            $book['asks'][] = [
                'price'=>($key = array_keys($data['asks'])[$i]),
                'value'=>$data['asks'][$key],
            ];
            $book['bids'][] = [
                'price'=>($key = array_keys($data['bids'])[$i]),
                'value'=>$data['bids'][$key],
            ];

            $i++;
        }

        return $book;
    }

    public function sendOrder(array $data): array
    {
        return [];
    }
}
