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
        $data  = $this->sdk->market()->getDepth(['symbol'=>$this->normalize($symbol),'depth'=>5])['data'];
        $book = [];
        $i = 0;

        while ($i < count($data['asks'])){
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

    public function sendOrder(string $symbol, float $lot, bool $isSell): array
    {
        // TODO: Implement sendOrder() method.
    }
}
