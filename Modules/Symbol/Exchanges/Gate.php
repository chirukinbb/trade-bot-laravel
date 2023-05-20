<?php

namespace Modules\Symbol\Exchanges;

use Illuminate\Support\Facades\Http;
use Lin\Gate\GateSpotV2;

class Gate extends Exchange
{
    protected object $sdk;
    protected string $name = 'gate';

    public function __construct()
    {
        $this->sdk = new GateSpotV2(env('GATE_API_KEY',''),env('GATE_API_SECRET',''));
    }

    public function symbols(): array
    {
        return array_map(function ($symbol) {
            return str_replace('_',':',strtoupper($symbol));
        }, $this->sdk->publics()->pairs());
    }

    public function isSymbolOnline(string $symbol): bool
    {
        try {
            $data  = json_decode(file_get_contents('https://api.gateio.ws/api/v4/margin/currency_pairs'));
         //   dd(str_replace(':','_',$symbol),$data);
            $symbolData = array_filter($data,function ($data) use ($symbol){
                return $data->id === str_replace(':','_',$symbol);
            });
           // dd(array_shift($symbolData));
        return !empty($symbolData) && array_shift($symbolData)->ststus === 1;
        }catch (\Exception $exception){
            return false;
        }
    }

    public function orderBook(string $symbol): array
    {
        $data = (array) json_decode(file_get_contents('https://api.gateio.ws/api/v4/spot/order_book?currency_pair='.$this->normalize($symbol).'&limit='.env('DEPTH')));
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
