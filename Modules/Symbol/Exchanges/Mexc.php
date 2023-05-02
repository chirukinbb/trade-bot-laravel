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
        $data  = $this->sdk->market()->getDepth(['symbol'=>$this->normalize($symbol),'depth'=>1])['data'];

        return [
            'ask'=>[
                'price'=>$data['asks'][0]['price'],
                'value'=>$data['asks'][0]['quantity'],
            ],
            'bid'=>[
                'price'=>$data['bids'][0]['price'],
                'value'=>$data['bids'][0]['quantity'],
            ],
        ];
    }

    public function sendOrder(string $symbol, float $lot, bool $isSell): array
    {
        // TODO: Implement sendOrder() method.
    }
}
