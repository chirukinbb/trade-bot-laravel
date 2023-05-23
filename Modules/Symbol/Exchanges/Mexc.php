<?php

namespace Modules\Symbol\Exchanges;

use Lin\Mxc\MxcSpot;

class Mexc extends Exchange
{
    protected object $sdk;
    protected string $name = 'mxc';

    public function __construct()
    {
        $this->sdk = new MxcSpot(env('MXC_API_KEY') ?? '',env('MXC_API_SECRET') ?? '');
    }

    public function symbols(): array
    {
        return array_map(function ($symbol) {
            return str_replace('_',':',$symbol['symbol']);
        }, $this->sdk->market()->getSymbols()['data']);
    }

    public function isSymbolOnline(string $symbol): bool
    {
        $symbolData = array_filter($this->sdk->market()->getSymbols()['data'],function ($data) use ($symbol){
            return $data['symbol'] === $this->normalize($symbol);
        });

        return !empty($symbolData) && end($symbolData)['state'] === 'ENABLED';
    }

    public function orderBook(string $symbol): array
    {
        $data  = $this->sdk->market()->getDepth(['symbol'=>$this->normalize($symbol),'depth'=>env('DEPTH')])['data'];

        return $this->extractBook($data);
    }

    public function sendOrder(array $data): array
    {
        $data = $this->sdk->order()->postPlace([
            'symbol'=>$data['symbol'],
            'trade_type'=>$data['side'] === 'sell' ? 'ASK' : 'BID',
            'order_type'=>'LIMIT_ORDER',
            'quantity'=> $data['volume'],
            'price'=>$data['total']['price']['end']
        ]);

        return $data['data'];
    }

    public function order(array $data)
    {
        $order = $this->sdk->order()->getDealDetail(['order_id'=>$data['id']]);
        $openOrders = $this->sdk->order()->getOpenOrders(['symbol'=>$data['symbol']]);
        $openOrderIds = array_map(function ($order){
            return $order['id'];
        },$openOrders['data']);

        return [
            'volume'=>$order['quantity'],
            'price'=>$order['price'],
            'side'=>$data['trade_type'],
            'status'=>in_array($data['id'],$openOrderIds) ? 'Open' : 'Close',
        ];
    }

    protected function extractBook(array $data)
    {
        $book = [];
        $i = 0;
        $count = min(count($data['asks']),count($data['bids']));

        while ($i < $count){
            $book['asks'][] = [
                'price'=>$data['asks'][$i]['price'],
                'value'=>$data['asks'][$i]['quantity'],
            ];
            $book['bids'][] = [
                'price'=>$data['bids'][$i]['price'],
                'value'=>$data['bids'][$i]['quantity'],
            ];

            $i++;
        }

        return $book;
    }
}
