<?php

return [
    'name' => 'Symbol',
    'exchanges'=>[
        'binance'=>[
            'title' =>'Binance',
            'separator'=>'',
            'lowercase'=>false,
            'adapter'=>\Modules\Symbol\Exchanges\Binance::class,
            'link'=>'https://www.binance.com/en/trade/{symbol}?theme=light&type=margin',
            'suffix'=>''
        ],
        'bitget'=>[
            'title' =>'Bitget',
            'separator'=>'',
            'lowercase'=>false,
            'adapter'=>\Modules\Symbol\Exchanges\Bitget::class,
            'link'=>'https://www.bitget.com/en/spot/{symbol}?type=cross',
            'suffix'=>'_UMCBL'
        ],
        'huobi'=>[
            'title' =>'Huobi',
            'separator'=>'',
            'lowercase'=>true,
            'adapter'=>\Modules\Symbol\Exchanges\Huobi::class,
            'link'=>'https://www.huobi.com/en-us/cross-margin/{symbol}',
            'suffix'=>''
        ],
        'okx'=>[
            'title' =>'OKX',
            'separator'=>'-',
            'lowercase'=>false,
            'adapter'=>\Modules\Symbol\Exchanges\OKX::class,
            'link'=>'https://www.okx.com/ru/trade-margin/{symbol}',
            'suffix'=>''
        ],
        'gate'=>[
            'title' =>'Gate',
            'separator'=>'_',
            'lowercase'=>true,
            'adapter'=>\Modules\Symbol\Exchanges\Gate::class,
            'link'=>'https://www.gate.io/ru/trade/{symbol}?tab=isolated_margin',
            'suffix'=>''
        ],
        'kucoin'=>[
            'title' =>'KuCoin',
            'separator'=>'-',
            'lowercase'=>false,
            'adapter'=>\Modules\Symbol\Exchanges\Kucoin::class,
            'link'=>'https://www.kucoin.com/ru/trade/margin/{symbol}',
            'suffix'=>''
        ],
        'mxc'=>[
            'title' =>'Mexc',
            'separator'=>'_',
            'lowercase'=>false,
            'adapter'=>\Modules\Symbol\Exchanges\Mexc::class,
            'link'=>'https://www.mexc.com/ru-RU/exchange/{symbol}',
            'suffix'=>''
        ],
        'bybit'=>[
            'title' =>'Bybit',
            'separator'=>'',
            'lowercase'=>false,
            'adapter'=>\Modules\Symbol\Exchanges\Bybit::class,
            'link'=>'https://www.bybit.com/en-US/trade/spot/{symbol}',
            'suffix'=>''
        ],
    ],
    'proxies'=>[
        [
            'address' => '45.154.231.127',
            'port' => '8000',
            'user' => 'h7h2Pe',
            'pass' => 'WqmY4F'
        ],
        [
            'address' => '45.154.231.232',
            'port' => '8000',
            'user' => 'h7h2Pe',
            'pass' => 'WqmY4F'
        ],
        [
            'address' => '196.18.14.163',
            'port' => '8000',
            'user' => 'VGsyR7',
            'pass' => 'vYyJ2Z'
        ],
        [
            'address' => '196.18.14.56',
            'port' => '8000',
            'user' => 'VGsyR7',
            'pass' => 'vYyJ2Z'
        ],
        [
            'address' => '196.17.223.159',
            'port' => '8000',
            'user' => 'VGsyR7',
            'pass' => 'vYyJ2Z'
        ],
        [
            'address' => '45.154.231.127',
            'port' => '8000',
            'user' => 'h7h2Pe',
            'pass' => 'WqmY4F'
        ],
    ]
];
