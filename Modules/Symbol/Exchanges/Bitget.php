<?php

namespace Modules\Symbol\Exchanges;

use Illuminate\Support\Facades\Http;
use Lin\Bitget\BitgetSpot;

class Bitget extends Exchange
{
    protected object $sdk;
    protected string $name = 'bitget';

    public function __construct(array $proxy,private array $symbolData = [],private array $assets =[])
    {
        $this->sdk  = new BitgetSpot(env('BITGET_API_KEY',''),env('BITGET_API_SECRET',''));
        $this->sdk->setOptions([
            'proxy'=>[
                'https' => "https://{$proxy['user']}:{$proxy['pass']}@{$proxy['address']}:{$proxy['port']}",
                'http' => "https://{$proxy['user']}:{$proxy['pass']}@{$proxy['address']}:{$proxy['port']}",
            ]
        ]);
        parent::__construct($proxy);
    }

    public function symbols(): array
    {
        return array_map(function ($symbol) {
            return $symbol['baseCoin'].':'.$symbol['quoteCoin'];
        }, $this->symbolData());
    }

    public function isSymbolOnline(string $symbol): bool
    {
        $symbolData = array_filter($this->symbolData,function ($data) use ($symbol){
            return $data['baseCoin'].$data['quoteCoin'] === $this->normalize($symbol);
        });

        return !empty($symbolData) && end($symbolData)->symbolStatus === 'normal';
    }

    public function orderBook(string $symbol): array
    {
        $data = json_decode($this->http->get('https://api.bitget.com/api/mix/v1/market/depth?symbol='.$this->normalize($symbol).'&limit='.env('DEPTH'))->body(),true)['data'];

        return $this->extractBook($data);
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

    public function symbolData()
    {
        $symbols = $this->http->get('https://api.bitget.com/api/spot/v1/public/products');

        return json_decode($symbols->body(),true)['data'];
    }

    public function coinInfo(string $coin)
    {
        $coins = array_filter($this->assets,function ($coinData) use ($coin){
            return $coinData['coinName'] === $coin;
        });

        if (empty($coins)){
            return false;
        }

        $coin = array_shift($coins)['chains'][0];

        return [
            'fee'=>$coin['withdrawFee'],
            'status'=>$coin['withdrawable'],
            'min'=>$coin['minWithdrawAmount'],
            'percent'=>true
        ];
    }

    public function getAssets()
    {
        $data  = $this->http->get('https://api.bitget.com/api/spot/v1/public/currencies');

        return json_decode($data->body(),true)['data'];
    }
}
