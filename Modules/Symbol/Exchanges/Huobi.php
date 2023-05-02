<?php

namespace Modules\Symbol\Exchanges;

use Lin\Huobi\HuobiSpot;

class Huobi extends Exchange
{
    protected object $sdk;
    protected string $name = 'huobi';

    public function __construct()
    {
        $this->sdk  = new HuobiSpot(/*env('HUOBI_API_KEY'),env('HUOBI_API_SECRET')*/);
    }

    public function symbols(): array
    {
        return array_map(function ($symbol) {
            return strtoupper($symbol['base-currency'].':'.$symbol['quote-currency']);
        }, $this->sdk->common()->getSymbols()['data']);
    }

    public function isSymbolOnline(string $symbol): bool
    {
        $symbolData = array_filter($this->sdk->common()->getSymbols()['data'],function ($data) use ($symbol){
            return $data['symbol'] === $this->normalize($symbol);
        });

        return !empty($symbolData) && end($symbolData)['state'] === 'online';
    }

    public function orderBook(string $symbol): array
    {
        $data = $this->sdk->market()->getDepth(['symbol'=>$this->normalize($symbol),'depth'=>5])['tick'];

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
