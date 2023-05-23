<?php

namespace Modules\Symbol\Exchanges;

use Illuminate\Support\Facades\Http;
use Lin\Bybit\BybitSpot;

class Bybit extends Exchange
{
    protected object $sdk;
    protected string $name = 'bybit';

    public function __construct()
    {
        $this->sdk = new BybitSpot(env('BYBIT_API_KEY',''),env('BYBIT_API_SECRET',''));
    }

    public function symbols(): array
    {
        return array_map(function ($symbol) {
            return $symbol['baseCurrency'].':'.$symbol['quoteCurrency'];
        }, $this->sdk->publics()->getSymbols()['result']);
    }

    public function isSymbolOnline(string $symbol): bool
    {
        $symbolData = array_filter($this->sdk->publics()->getSymbols()['result'],function ($data) use ($symbol){
            return $data['name'] === $this->normalize($symbol);
        });

        return !empty($symbolData) && end($symbolData)['showStatus'];
    }

    public function orderBook(string $symbol): array
    {
        $data = $this->sdk->publics()->getDepth(['symbol'=>$this->normalize($symbol),'limit'=>env('DEPTH')])['result'];

        return $this->extractBook($data);
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
        return [
            'X-BAPI-SIGN-TYPE'=>2,
            'X-BAPI-SIGN'=>hash_hmac('sha256',http_build_query($body),env('BYBIT_API_SECRET')),
            'BAPI-API-KEY'=>env('BYBIT_API_KEY'),
            'X-BAPI-TIMESTAMP'=>now()->timestamp*1000,
            'X-BAPI-RECV-WINDOW'=>5000
        ];
    }
}
