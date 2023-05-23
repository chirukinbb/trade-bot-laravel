<?php

namespace Modules\Symbol\Exchanges;

use Binance\API;
use Illuminate\Support\Facades\Http;

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
        $symbols = array_filter($this->sdk->exchangeInfo()['symbols'],function ($symbol){
            return in_array('MARGIN',$symbol['permissions']);
        });

        return array_map(function ($symbol) {
            return $symbol['baseAsset'].':'.$symbol['quoteAsset'];
        }, $symbols);
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
        $data = $this->sdk->depth($this->normalize($symbol),env('DEPTH'));

        return $this->extractBook($data);
    }

    public function sendOrder(array $data): array
    {
        $data = Http::post('https://testnet.binance.vision/sapi/v1/margin/order',[
            'symbol'=>$data['symbol'],
            'quantity'=>$data['volume'],
            'side'=>$data['side'],
            'type'=>'MARKET',
            'timestamp'=>now()->timestamp,
            'signature'=>hash_hmac('sha256',http_build_query($data),env('BINANCE_API_SECRET'))
        ]);

        return json_decode($data->body())->orderId;
    }

    public function order(array $data)
    {
        $data = json_decode(Http::post('https://testnet.binance.vision/api/v3/order',[
            'symbol'=>$data['symbol'],
            'orderId'=>$data['orderId'],
            'timestamp'=>now()->timestamp,
            'signature'=>hash_hmac('sha256',http_build_query($data),env('BINANCE_API_SECRET'))
        ])->body(),true);

        return [
            'volume'=>$data['origQty'],
            'price'=>$data['price'],
            'side'=>$data['side']
        ];
    }

    public function withdrawalFee(string $coin)
    {
        return $this->sdk->withdrawFee($coin)['withdrawFee'];
    }

    protected function extractBook(array $data)
    {
        $book = [];
        $i = 0;
        $count = min(count($data['asks']),count($data['bids']));

        while ($i < $count){
            $book['asks'][] = [
                'price'=>($key = array_keys($data['asks'])[$i]),
                'value'=>$data['asks'][$key],
            ];
            $book['bids'][] = [
                'price'=>($key = array_keys($data['bids'])[$i]),
                'value'=>$data['bids'][$key],
            ];

            $i++;
        }

        return $book;
    }
}
