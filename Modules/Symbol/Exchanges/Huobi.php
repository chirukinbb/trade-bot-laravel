<?php

namespace Modules\Symbol\Exchanges;

use Illuminate\Support\Facades\Http;
use Lin\Huobi\HuobiSpot;

class Huobi extends Exchange
{
    protected object $sdk;
    protected string $name = 'huobi';

    public function __construct(array $proxy)
    {
        $this->sdk  = new HuobiSpot(env('HUOBI_API_KEY',''),env('HUOBI_API_SECRET',''));
        parent::__construct($proxy);
    }

    public function symbols(): array
    {
        $symbols = $this->http->get('https://api.huobi.pro/v1/common/symbols');

        return array_map(function ($symbol) {
            return strtoupper($symbol['base-currency'].':'.$symbol['quote-currency']);
        }, json_decode($symbols->body(),true)['data']);
    }

    public function isSymbolOnline(string $symbol): bool
    {
        $symbols = $this->http->get('https://api.huobi.pro/v1/common/symbols');

        $symbolData = array_filter(json_decode($symbols->body(),true)['data'],function ($data) use ($symbol){
            return $data['symbol'] === $this->normalize($symbol);
        });

        return !empty($symbolData) && end($symbolData)['state'] === 'online';
    }

    public function orderBook(string $symbol): array
    {
        $data = $this->http->get('https://api.huobi.pro/market/depth?'.http_build_query(['symbol'=>$this->normalize($symbol),'depth'=>20,'type'=>'step0']));

        return $this->extractBook(json_decode($data->body(),true)['tick']);
    }

    public function sendOrder(array $data): array
    {
        $accounts = $this->sdk->account()->get()['data'];
        $accountID = 0;

        foreach ($accounts as $account){
            if ($accounts['state'] === 'working' && $account['type'] === 'margin'){
                $accountID = $account['id'];
            }
        }

        $data = $this->sdk->order()->postPlace([
            'symbol'=>$data['symbol'],
            'amount'=> $data['volume'],
            'type'=>$data['side'].'-market',
            'account-id'=>$accountID,
            'price'=>$data['total']['price']['end']
        ]);

        return $data['data'];
    }

    public function order(array $data)
    {
        $order = $this->sdk->order()->get(['orderId'=>$data['id']]);
        $side = explode('-',$order['type']);

        return [
            'volume'=>$order['amount'],
            'price'=>$order['price'],
            'side'=>$side[0],
            'status'=>$order['state'],
        ];
    }
}
