<?php

namespace Modules\Symbol\Exchanges;

use Binance\API;
use Illuminate\Support\Facades\Http;

class Binance extends Exchange
{
    protected object $sdk;
    protected string $name = 'binance';

    public function __construct(array $proxy,private array $symbolData = [],private array $assets =[])
    {
        $this->sdk = new \Modules\Trader\SDK\Binance(env('BINANCE_API_KEY'), env('BINANCE_API_SECRET'));
        $this->sdk->setProxy(array_merge($proxy,['proto'=>'https']));
    }

    public function symbols(): array
    {
        return array_map(function ($symbol) {
            return $symbol['baseAsset'].':'.$symbol['quoteAsset'];
        }, $this->symbolData());
    }

    public function isSymbolOnline(string $symbol): bool
    {
        $symbolData = array_filter($this->symbolData, function ($data) use ($symbol) {
            return $data['symbol'] === $this->normalize($symbol);
        });

        return !empty($symbolData) && end($symbolData)['status'] === 'TRADING';
    }

    public function orderBook(string $symbol): array
    {
        $data = $this->sdk->depth($this->normalize($symbol), env('DEPTH'));

        return $this->extractBook($data);
    }

    public function sendOrder(array $data): array
    {
        $this->sdk->clearProxy();
        $data = $this->sdk->order($data['side'],$data['symbol'],$data['volume'],$data['price'],'MARKET');

        return json_decode($data->body())->orderId;
    }

    public function order(array $data)
    {
        $data = $this->sdk->orders($data['symbol']);
        $data = json_decode(Http::post('https://binance.vision/api/v3/order', [
            'symbol' => $data['symbol'],
            'orderId' => $data['orderId'],
            'timestamp' => now()->timestamp,
            'signature' => hash_hmac('sha256', http_build_query($data), env('BINANCE_API_SECRET'))
        ])->body(), true);

        return [
            'volume' => $data['origQty'],
            'price' => $data['price'],
            'side' => $data['side']
        ];
    }

    public function coinInfo(string $coin)
    {
        $coin = $this->assets['assetDetail'][$coin];

        if (empty($coins)){
            return false;
        }

        return [
            'fee'=>$coin['withdrawFee'],
            'status'=>$coin['withdrawStatus'],
            'min'=>$coin['minWithdrawAmount'],
            'percent'=>false
        ];
    }

    public function getAssets()
    {
        return $this->sdk->assetDetail();
    }

    protected function extractBook(array $data)
    {
        $book = [];
        $i = 0;
        $count = min(count($data['asks']), count($data['bids']));

        while ($i < $count) {
            $book['asks'][] = [
                'price' => ($key = array_keys($data['asks'])[$i]),
                'value' => $data['asks'][$key],
            ];
            $book['bids'][] = [
                'price' => ($key = array_keys($data['bids'])[$i]),
                'value' => $data['bids'][$key],
            ];

            $i++;
        }

        return $book;
    }

    public function symbolData()
    {
        return$this->sdk->exchangeInfo()['symbols'];
    }

    public function transfer(array $data)
    {
        $this->sdk->withdraw($data['coin'],$data['address'],$data['amount']);
    }
}
