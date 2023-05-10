<?php

namespace Modules\Symbol\Exchanges;

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
        $data  = json_decode(file_get_contents('https://api.gateio.ws/api/v4/spot/currency_pairs/'.$this->normalize($symbol)));

        return $data->trade_status  === 'tradable';
    }

    public function orderBook(string $symbol): array
    {
        $data = (array) json_decode(file_get_contents('https://api.gateio.ws/api/v4/spot/order_book?currency_pair='.$this->normalize($symbol).'&limit=5'));
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
        return [];
    }
}
