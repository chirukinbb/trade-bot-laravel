<?php

namespace Modules\Symbol\Exchanges;

class Kucoin extends Exchange
{
    protected object $sdk;
    protected string $name = 'kucoin';

    public function __construct()
    {
        $this->sdk = new \Lin\Ku\Kucoin(env('KUCOIN_API_KEY',''),env('KUCOIN_API_SECRET',''));
    }

    public function symbols(): array
    {
        return array_map(function ($symbol) {
            return $symbol['baseCurrency'].':'.$symbol['quoteCurrency'];
        }, $this->sdk->market()->getSymbols()['data']);
    }

    public function isSymbolOnline(string $symbol): bool
    {
        $symbolData = array_filter($this->sdk->market()->getSymbols()['data'],function ($data) use ($symbol){
            return $data['name'] === $this->normalize($symbol);
        });

        return !empty($symbolData) && end($symbolData)['enableTrading'];
    }

    public function orderBook(string $symbol): array
    {
        $data  = $this->sdk->market()->getOrderBookLevel2_20(['symbol'=>$this->normalize($symbol)])['data'];
        $book = [];
        $i = 0;

        while ($i < count($data['asks'])){
            $book['asks'][] = [
                'price'=>$data['asks'][$i][0],
                'value'=>$data['asks'][$i][1],
            ];
            $book['bids'][] = [
                'price'=>$data['asks'][$i][0],
                'value'=>$data['asks'][$i][1],
            ];

            $i++;
        }

        return $book;
    }

    public function sendOrder(string $symbol, float $lot, bool $isSell): array
    {
        // TODO: Implement sendOrder() method.
    }
}
