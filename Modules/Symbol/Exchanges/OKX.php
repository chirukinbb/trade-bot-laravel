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
        $data = (array)json_decode(file_get_contents('https://www.okx.com/api/v5/market/books?instId='.$this->normalize($symbol).'&sz='.env('DEPTH')))->data[0];
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
        $data = $this->sdk->order()->post([
            'instrument_id'=>$data['symbol'],
            'side'=>$data['side'],
            'price'=>$data['total']['price']['end'],
            'size'=>$data['volume'],
            'margin_trading'=>2,
        ]);

        return $data['data'];
    }

    public function order(array $data)
    {
        $order = $this->sdk->order()->get([
            'order_id'=>$data['id'],
            'symbol'=>$data['symbol']
            ]);

        return [
            'volume'=>$order['size'],
            'price'=>$order['price'],
            'side'=>$data['side'],
            'status'=>true,
        ];
    }
}
