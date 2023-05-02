<?php

namespace Modules\Symbol\Exchanges;

use Lin\Bitget\BitgetSpot;

class Bitget extends Exchange
{
    protected object $sdk;
    protected string $name = 'bitget';

    public function __construct()
    {
        $this->sdk  = new BitgetSpot(/*env('BITGET_API_KEY'),env('BITGET_API_SECRET')*/);
    }

    public function symbols(): array
    {
        return array_map(function ($symbol) {
            return $symbol->baseCoin.':'.$symbol->quoteCoin;
        }, (array)json_decode(file_get_contents('https://api.bitget.com/api/mix/v1/market/contracts?productType=umcbl'))->data);
    }

    public function isSymbolOnline(string $symbol): bool
    {
        $symbolData = array_filter((array)json_decode(file_get_contents('https://api.bitget.com/api/mix/v1/market/contracts?productType=umcbl'))->data,function ($data) use ($symbol){
            return $data->baseCoin.$data->quoteCoin === $this->normalize($symbol);
        });

        return !empty($symbolData) && end($symbolData)->symbolStatus === 'normal';
    }

    public function orderBook(string $symbol): array
    {
        $data = json_decode(file_get_contents('https://api.bitget.com/api/mix/v1/market/depth?symbol='.$this->normalize($symbol).'=&limit=5'))->data;

        return [
            'ask'=>[
                'price'=>$data->asks[0][0],
                'value'=>$data->asks[0][1],
            ],
            'bid'=>[
                'price'=>$data->bids[0][0],
                'value'=>$data->bids[0][1],
            ],
        ];
    }

    public function sendOrder(string $symbol, float $lot, bool $isSell): array
    {
        return $this->sdk->order()->post();
    }
}
