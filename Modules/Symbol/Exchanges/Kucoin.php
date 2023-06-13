<?php

namespace Modules\Symbol\Exchanges;

class Kucoin extends Exchange
{
    protected object $sdk;
    protected string $name = 'kucoin';

    public function __construct(array $proxy,private array $symbolData = [],private array $assets =[])
    {
        $this->sdk = new \Lin\Ku\Kucoin(env('KUCOIN_API_KEY',''),env('KUCOIN_API_SECRET',''));
        parent::__construct($proxy);
        $this->sdk->setOptions([
            'proxy'=>[
                'https' => "https://{$proxy['user']}:{$proxy['pass']}@{$proxy['address']}:{$proxy['port']}",
                'http' => "https://{$proxy['user']}:{$proxy['pass']}@{$proxy['address']}:{$proxy['port']}",
            ]
        ]);
    }

    public function symbols(): array
    {
        return array_map(function ($symbol) {
            return $symbol['baseCurrency'].':'.$symbol['quoteCurrency'];
        }, $this->symbolData());
    }

    public function isSymbolOnline(string $symbol): bool
    {
        $symbolData = array_filter($this->symbolData,function ($data) use ($symbol){
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

    public function symbolData()
    {
        $symbols = $this->http->get('https://api.kucoin.com/api/v1/symbols');

        return json_decode($symbols->body(),true)['data'];
    }

    public function coinInfo(string $coin)
    {
        $coin  = array_filter($this->assets,function ($data) use ($coin){
            return $data['currency'] === $coin;
        });
        $coin = array_shift($coin);

        return [
            'fee'=>$coin['withdrawalMinFee'],
            'status'=>$coin['isWithdrawEnabled'],
            'min'=>$coin['withdrawalMinSize'],
            'percent'=>false
        ];
    }

    public function getAssets()
    {
        return $this->sdk->currencies()->getAll()['data'];
    }
}
