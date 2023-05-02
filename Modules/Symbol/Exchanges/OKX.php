<?php

namespace Modules\Symbol\Exchanges;

use Lin\Okex\OkexSpot;

class OKX extends Exchange
{
    protected object $sdk;
    protected string $name = 'okx';

    public function __construct()
    {
        $this->sdk = new OkexSpot(env('OKX_API_KEY') ?? '',env('OKX_API_SECRET') ?? '');
    }

    public function symbols(): array
    {
        return array_map(function ($symbol) {
            $symbol = (array)$symbol;
            return $symbol['baseCcy'].':'.$symbol['quoteCcy'];
        },json_decode(file_get_contents('https://www.okx.com/api/v5/public/instruments?instType=SPOT'))->data);
    }

    public function isSymbolOnline(string $symbol): bool
    {
        $symbolData = array_filter(json_decode(file_get_contents('https://www.okx.com/api/v5/public/instruments?instType=SPOT'))->data,function ($data) use ($symbol){
            return $data->instId === $this->normalize($symbol);
        });

        return !empty($symbolData) && end($symbolData)->state === 'live';
    }

    public function orderBook(string $symbol): array
    {
        $data = (array)json_decode(file_get_contents('https://www.okx.com/api/v5/market/books?instId='.$this->normalize($symbol)))->data[0];

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
