<?php

namespace Modules\Symbol\Exchanges;

use Illuminate\Support\Facades\Http;
use Lin\Bybit\BybitSpot;

class Bybit extends Exchange
{
    protected object $sdk;
    protected string $name = 'bybit';

    public function __construct(array $proxy,private array $symbolData = [])
    {
        $this->sdk = new BybitSpot(env('BYBIT_API_KEY',''),env('BYBIT_API_SECRET',''));
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
        },$this->symbolData);
    }

    public function isSymbolOnline(string $symbol): bool
    {
        $symbolData = array_filter($this->symbolData,function ($data) use ($symbol){
            return $data['name'] === $this->normalize($symbol);
        });

        return !empty($symbolData) && end($symbolData)['showStatus'];
    }

    public function orderBook(string $symbol): array
    {
        $data = $this->http->get('https://api.bybit.com/spot/quote/v1/depth?symbol='.$this->normalize($symbol).'&limit='.env('DEPTH'));

        return $this->extractBook(json_decode($data->body(),true)['result']);
    }

    public function link(string $symbol)
    {
        $link = config('symbol.exchanges.'.$this->name.'.link');

        return str_replace('{symbol}',str_replace(':','/',$symbol),$link);
    }

    public function sendOrder(array $data): array
    {
        $body = [
            'symbol'=>$data['symbol'],
            'qty'=>$data['volume'],
            'side'=>$data['side'],
            'orderType'=>'Market',
            'timeInForce'=>'GoodTillCancel'
        ];

        $data = Http::withHeaders($body)
        ->post('https://api-testnet.bybit.com/unified/v3/order/create',$body);

        return json_decode($data->body())->orderId;
    }

    public function order(array $data)
    {
        $data = json_decode(Http::withHeaders($this->headers([]))
        ->get('https://api-testnet.bybit.com/unified/v3/order/',[
            'symbol'=>$data['symbol'],
            'orderId'=>$data['orderId']
        ])->body(),true);

        return [
            'volume'=>$data['result']['list'][0]['qty'],
            'price'=>$data['result']['list'][0]['price'],
            'side'=>$data['result']['list'][0]['side'],
            'status'=>$data['result']['list'][0]['orderStatus'],
        ];
    }

    private function headers($body)
    {
        ksort($body);

        $timestamp = round(microtime(true) * 1000);
        $recv_window = 5000;
        $string = $timestamp.env('BYBIT_API_KEY').$recv_window.http_build_query($body);

        return [
            'X-BAPI-SIGN'=>hash_hmac('sha256',$string,env('BYBIT_API_SECRET')),
            'X-BAPI-API-KEY'=>env('BYBIT_API_KEY'),
            'X-BAPI-TIMESTAMP'=>$timestamp,
            'X-BAPI-RECV-WINDOW'=>5000
        ];
    }

    public function symbolData()
    {
        $symbols = $this->http->get('https://api.bybit.com/spot/v1/symbols');

        return json_decode($symbols->body(),true)['result'];
    }

    public function coinInfo(string $coin)
    {
        $coins = $this->http->withHeaders($this->headers([
            'coin'=>$coin
        ]))->get('https://api.bybit.com/v5/asset/coin/query-info?coin='.$coin);
        $coin = json_decode($coins->body(),true)['result']['rows'][0]['chains'][0];

        return [
            'fee'=>$coin['withdrawFee'],
            'status'=>!!$coin['chainWithdraw'],
            'min'=>$coin['withdrawMin'],
            'percent'=>false
        ];
    }
}
