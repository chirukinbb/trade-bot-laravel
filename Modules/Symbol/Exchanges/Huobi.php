<?php

namespace Modules\Symbol\Exchanges;

use Illuminate\Support\Facades\Http;
use Lin\Huobi\HuobiSpot;

class Huobi extends Exchange
{
    protected object $sdk;
    protected string $name = 'huobi';

    public function __construct(array $proxy,private array $symbolData = [],private array $assets =[])
    {
        $this->sdk  = new HuobiSpot(env('HUOBI_API_KEY',''),env('HUOBI_API_SECRET',''));
        parent::__construct($proxy);
        $this->sdk->setOptions([
            'proxy'=>[
                'https' => "https://{$proxy['user']}:{$proxy['pass']}@{$proxy['address']}:{$proxy['port']}",
                'http' => "https://{$proxy['user']}:{$proxy['pass']}@{$proxy['address']}:{$proxy['port']}",
            ]
        ]);
    }

    public function symbols(): array
    {

        return array_map(function ($symbol) {
            return strtoupper($symbol['base-currency'].':'.$symbol['quote-currency']);
        }, $this->symbolData());
    }

    public function isSymbolOnline(string $symbol): bool
    {
        $symbolData = array_filter($this->symbolData,function ($data) use ($symbol){
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

    public function symbolData()
    {
        $symbols = $this->http->get('https://api.huobi.pro/v1/common/symbols');

        return json_decode($symbols->body(),true)['data'];
    }

    public function link(string $symbol)
    {
        $link = config('symbol.exchanges.'.$this->name.'.link');

        return str_replace('{symbol}',str_replace(':','_',strtolower($symbol)),$link);
    }

    public function coinInfo(string $coin)
    {
        $coin = array_filter($this->assets,function ($asset) use ($coin){
            return $asset['currency'] === strtolower($coin);
        });
        $coin = array_shift($coin);

        return [
            'fee'=>$coin['transactFeeWithdraw'],
            'status'=>$coin['withdrawStatus'] === 'allowed',
            'min'=>$coin['minWithdrawAmt'],
            'percent'=>false
        ];
    }

    public function getAssets()
    {
        return $this->sdk->reference()->getCurrencies()['data'];
    }
}
