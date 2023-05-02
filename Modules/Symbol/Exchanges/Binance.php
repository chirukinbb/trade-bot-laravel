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
        $data = $this->sdk->depth($this->normalize($symbol),1);

        return [
            'ask'=>[
                'price'=>($key = array_keys($data['asks'])[0]),
                'value'=>$data['asks'][$key],
            ],
            'bid'=>[
                'price'=>($key = array_keys($data['bids'])[0]),
                'value'=>$data['bids'][$key],
            ],
        ];
    }

    public function sendOrder(string $symbol,float $lot, bool $isSell): array
    {
        $symbol = $this->normalize($symbol);

        return $isSell ? $this->sdk->marketSell($symbol,$lot) : $this->sdk->marketBuy($symbol,$lot);
    }
}
