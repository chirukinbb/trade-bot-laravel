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
        $data  = $this->sdk->market()->getOrderBookLevel2_100(['symbol'=>$this->normalize($symbol)])['data'];

        return $this->extractBook($data);
    }

    public function sendOrder(array $data): array
    {
        $data = $this->sdk->order()->post([
            'symbol'=>$data['symbol'],
            'size'=> $data['volume'],
            'type'=>'market',
            'side'=>$data['side'],
            'price'=>$data['total']['price']['end']
        ]);

        return $data['orderId'];
    }

    public function order(array $data)
    {
        $order = $this->sdk->order()->get([
            'orderId'=>$data['id'],
            'symbol'=>$data['symbol']
        ]);

        return [
            'volume'=>$order['size'],
            'price'=>$order['price'],
            'side'=>$order['side'],
            'status'=>$order['active'],
        ];
    }
}
