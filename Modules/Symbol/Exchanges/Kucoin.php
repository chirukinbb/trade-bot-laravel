<?php

namespace Modules\Symbol\Exchanges;

class Kucoin extends Exchange
{
    protected object $sdk;
    protected string $name = 'kucoin';

    public function __construct(array $proxy)
    {
        $this->sdk = new \Lin\Ku\Kucoin(env('KUCOIN_API_KEY',''),env('KUCOIN_API_SECRET',''));
        parent::__construct($proxy);
    }

    public function symbols(): array
    {
        $symbols = $this->http->get('https://api.kucoin.com/api/v1/symbols');

        return array_map(function ($symbol) {
            return $symbol['baseCurrency'].':'.$symbol['quoteCurrency'];
        }, json_decode($symbols->body(),true)['data']);
    }

    public function isSymbolOnline(string $symbol): bool
    {
        $symbols = $this->http->get('https://api.kucoin.com/api/v1/symbols');

        $symbolData = array_filter(json_decode($symbols->body(),true)['data'],function ($data) use ($symbol){
            return $data['name'] === $this->normalize($symbol);
        });

        return !empty($symbolData) && end($symbolData)['enableTrading'];
    }

    public function orderBook(string $symbol): array
    {
        $data  = $this->http->get('https://api.kucoin.com/api/v1/market/orderbook/level2_100?'.http_build_query(['symbol'=>$this->normalize($symbol)]));

        return $this->extractBook(json_decode($data->body(),true)['data']);
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
