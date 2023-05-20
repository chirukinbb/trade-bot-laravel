<?php

namespace Modules\Symbol\Exchanges\Websocket;

use Modules\Symbol\Exchanges\Exchange;
use WSSC\Components\ClientConfig;
use WSSC\WebSocketClient;

class Binance extends Exchange
{
    private WebSocketClient $client;

    /**
     * @throws \WSSC\Exceptions\ConnectionException
     * @throws \WSSC\Exceptions\BadUriException
     */
    public function __construct()
    {
        $this->client = new WebSocketClient('wss://ws-api.binance.com:443/ws-api/v3',new ClientConfig());
    }

    public function betterPrices(array|string $symbol)
    {
        $response  = $this->request('ticker.book',['symbol'=>$symbol]);

        return [
            'askPrice'=>$response['result']['askPrice'],
            'bidPrice'=>$response['result']['bidPrice']
        ];
    }

    public function symbols(): array
    {
        return [];
    }

    public function isSymbolOnline(string $symbol): bool
    {
        // TODO: Implement isSymbolOnline() method.
    }

    public function orderBook(string $symbol): array
    {
        $response = $this->request('depth',[
            'symbol'=>$symbol,
            'limit'=>env('DEPTH')
        ]);

        return $this->getOrderBook($response['result']);
    }

    public function sendOrder(array $data): array
    {
        $params  = [
            'apiKey'=>env('BINANCE_API_KEY'),
            'quantity'=>$data['volume'],
            'side'=>$data['side'],
            'symbol'=>$data['symbol'],
            'timestamp'=>now()->timestamp*1000,
            'type'=>'MARKET'

        ];

        $response = $this->request('order.test',array_merge(
            $params,
            ['signature'=>$this->signature($params)]
        ));

        return $response['result']['orderId'];
    }

    protected function request(string $method,array $params)
    {
        $this->client->send(json_encode([
            'id'=>now()->timestamp,
            'method'=>$method,
            'params'=>$params
        ]));

        return json_decode($this->client->receive(),true);
    }

    protected function getOrderBook(array $data): array
    {
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

    private function signature(array $body): string
    {
        return hash_hmac('sha256',http_build_query($body),env('BINANCE_API_SECRET'));
    }
}
