<?php

namespace Modules\Symbol\Exchanges;

use Illuminate\Support\Facades\Http;
use Lin\Bitget\BitgetSpot;

class Bitget extends Exchange
{
    protected object $sdk;
    protected string $name = 'bitget';

    public function __construct()
    {
        $this->sdk  = new BitgetSpot(env('BITGET_API_KEY',''),env('BITGET_API_SECRET',''));
        //dd($this->sdk->common()->getSymbols());
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
        $data = json_decode(file_get_contents('https://api.bitget.com/api/mix/v1/market/depth?symbol='.$this->normalize($symbol).'&limit='.env('DEPTH')))->data;
        $book = [];
        $i = 0;

        while ($i < count($data->asks)){
            $book['asks'][] = [
                'price'=>$data->asks[$i][0],
                'value'=>$data->asks[$i][1],
            ];
            $book['bids'][] = [
                'price'=>$data->bids[$i][0],
                'value'=>$data->bids[$i][1],
            ];

            $i++;
        }

        return $book;
    }

    public function sendOrder(array $data): array
    {
        $body = [
            'symbol'=>$data['symbol'],
            'baseQuantity'=>$data['volume'],
            'side'=>$data['side'],
            'orderType'=>'market',
            'loanType'=>'normal',
            'timeInForce'=>'gtc'
        ];

        $data = Http::withHeaders($this->headers('POST','/api/margin/v1/cross/order/placeOrder',$body))
            ->post('https://api.bitget.com/api/margin/v1/cross/order/placeOrder',$body);

        return json_decode($data->body())->data->orderId;
    }

    public function order(array $data)
    {
        $data = json_decode(Http::withHeaders($this->headers('POST','/api/margin/v1/cross/order/fills',[]))
            ->get('https://api.bitget.com/api/margin/v1/cross/order/fills',[
                'symbol'=>$data['symbol'],
                'orderId'=>$data['id'],
                'startTime'=>0
            ])->body(),true);

        return [
            'volume'=>$data['resultList'][0]['fillQuantity'],
            'price'=>$data['resultList'][0]['fillPrice'],
            'side'=>$data['resultList'][0]['side']
        ];
    }

    private function sign($message, $secret_key) {
        $mac = hash_hmac('sha256', $message, $secret_key, true);
        $d = $mac;
        return base64_encode($d);
    }

    private function pre_hash($timestamp, $method, $request_path, $body) {
        return $timestamp . strtoupper($method) . $request_path . $body;
    }

    private function headers($method,$path,$body)
    {
        return ['ACCESS-SIGN'=>$this->sign($this->pre_hash(
            ($ts=microtime()),$method,$path,$body
        ),env('BITGET_API_SECRET')),
            'ACCESS-KEY'=>env('BITGET_API_KEY'),
            'ACCESS-TIMESTAMP'=>$ts];
    }
}
