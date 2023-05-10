<?php

namespace Modules\Symbol\Exchanges;

use Lin\Huobi\HuobiSpot;

class Huobi extends Exchange
{
    protected object $sdk;
    protected string $name = 'huobi';

    public function __construct()
    {
        $this->sdk  = new HuobiSpot(env('HUOBI_API_KEY',''),env('HUOBI_API_SECRET',''));
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

    public function sendOrder(array $data): array
    {
        return [];
    }
}
