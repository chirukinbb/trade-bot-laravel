<?php

namespace Modules\Symbol\Exchanges;

use Illuminate\Support\Facades\Http;
use Lin\Gate\GateSpotV2;

class Gate extends Exchange
{
    protected object $sdk;
    protected string $name = 'gate';

    public function __construct(array $proxy)
    {
        $this->sdk = new GateSpotV2(env('GATE_API_KEY',''),env('GATE_API_SECRET',''));
        parent::__construct($proxy);
    }

    public function symbols(): array
    {
        $symbols = $this->http->get('https://api.gateio.la/api2/1/pairs');

        return array_map(function ($symbol) {
            return str_replace('_',':',strtoupper($symbol));
        }, json_decode($symbols->body(),true));
    }

    public function isSymbolOnline(string $symbol): bool
    {
        try {
            $symbols = $this->http->get('https://api.gateio.ws/api/v4/margin/currency_pairs');
            $data  = json_decode($symbols->body(),true);

            $symbolData = array_filter($data,function ($data) use ($symbol){
                return $data->id === str_replace(':','_',$symbol);
            });
        return !empty($symbolData) && array_shift($symbolData)->ststus === 1;
        }catch (\Exception $exception){
            return false;
        }
    }

    public function orderBook(string $symbol): array
    {
        $data = $this->http->get(('https://api.gateio.ws/api/v4/spot/order_book?currency_pair='.$this->normalize($symbol).'&limit='.env('DEPTH')));

        return $this->extractBook(json_decode($data->body(),true));
    }

    public function sendOrder(array $data): array
    {
        $body = [
            'symbol'=>$data['symbol'],
            'amount'=>($data['side'] === 'sell') ? $data['volume'] : $data['quote'],
            'side'=>$data['side'],
            'type'=>'market',
            'account'=>'cross-margin'
        ];

        $this->sdk->privates()->{$data['side']}();

        $data = Http::withHeaders($this->headers($body,'POST','https://fx-api-testnet.gateio.ws/api/v4/spot/orders'))
            ->post('https://fx-api-testnet.gateio.ws/api/v4/spot/orders',$body);

        return json_decode($data->body())->orderId;
    }

    public function order(array $data)
    {
        $data = json_decode(Http::withHeaders($this->headers([],'GET','https://fx-api-testnet.gateio.ws/api/v4/spot/orders/'.$data['id']))
            ->get('https://fx-api-testnet.gateio.ws/api/v4/spot/orders/'.$data['id'])
            ->body(),true);

        return [
            'volume'=>['amount'],
            'price'=>$data['price'],
            'side'=>$data['side'],
            'status'=>$data['status'],
        ];
    }

    private function headers($body,$method,$url)
    {
        return array_merge([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ],$this->gen_sign($method,$url,'',json_encode($body)));
    }

    private function gen_sign($method, $url, $query_string = null, $payload_string = null)
    {
        $t = now()->timestamp;
        $hashed_payload = hash('sha512', $payload_string ?: '', false);
        $s = "{$method}\n{$url}\n{$query_string}\n{$hashed_payload}\n{$t}";
        $sign = hash_hmac('sha512', $s, env('GATE_API_SECRET'), false);

        return [
            'KEY' => env('GATE_API_KEY'),
            'Timestamp' => $t,
            'SIGN' => $sign
        ];
    }
}
