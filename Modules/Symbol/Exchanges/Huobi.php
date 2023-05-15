<?php

namespace Modules\Symbol\Exchanges;

use Illuminate\Support\Facades\Http;
use Lin\Huobi\HuobiSpot;

class Huobi extends Exchange
{
    protected object $sdk;
    protected string $name = 'huobi';

    public function __construct()
    {
        $this->sdk  = new HuobiSpot(env('HUOBI_API_KEY',''),env('HUOBI_API_SECRET',''));
    }

    public function symbols(): array
    {
        return array_map(function ($symbol) {
            return strtoupper($symbol['base-currency'].':'.$symbol['quote-currency']);
        }, $this->sdk->common()->getSymbols()['data']);
    }

    public function isSymbolOnline(string $symbol): bool
    {
        $symbolData = array_filter($this->sdk->common()->getSymbols()['data'],function ($data) use ($symbol){
            return $data['symbol'] === $this->normalize($symbol);
        });

        return !empty($symbolData) && end($symbolData)['state'] === 'online';
    }

    public function orderBook(string $symbol): array
    {
        $data = $this->sdk->market()->getDepth(['symbol'=>$this->normalize($symbol),'depth'=>5])['tick'];
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
