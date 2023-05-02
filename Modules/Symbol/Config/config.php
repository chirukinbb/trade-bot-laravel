<?php

return [
    'name' => 'Symbol',
    'exchanges'=>[
        'binance'=>[
            'title' =>'Binance',
            'separator'=>'',
            'lowercase'=>false,
            'adapter'=>\Modules\Symbol\Exchanges\Binance::class,
            'suffix'=>''
        ],
        'bitget'=>[
            'title' =>'Bitget',
            'separator'=>'',
            'lowercase'=>false,
            'adapter'=>\Modules\Symbol\Exchanges\Bitget::class,
            'suffix'=>'_UMCBL'
        ],
        'huobi'=>[
            'title' =>'Huobi',
            'separator'=>'',
            'lowercase'=>true,
            'adapter'=>\Modules\Symbol\Exchanges\Huobi::class,
            'suffix'=>''
        ],
        'okx'=>[
            'title' =>'OKX',
            'separator'=>'-',
            'lowercase'=>false,
            'adapter'=>\Modules\Symbol\Exchanges\OKX::class,
            'suffix'=>''
        ],
        'gate'=>[
            'title' =>'Gate',
            'separator'=>'_',
            'lowercase'=>true,
            'adapter'=>\Modules\Symbol\Exchanges\Gate::class,
            'suffix'=>''
        ],
        'kucoin'=>[
            'title' =>'KuCoin',
            'separator'=>'-',
            'lowercase'=>false,
            'adapter'=>\Modules\Symbol\Exchanges\Kucoin::class,
            'suffix'=>''
        ],
        'mxc'=>[
            'title' =>'Mexc',
            'separator'=>'_',
            'lowercase'=>false,
            'adapter'=>\Modules\Symbol\Exchanges\Mexc::class,
            'suffix'=>''
        ],
        'bybit'=>[
            'title' =>'Bybit',
            'separator'=>'',
            'lowercase'=>false,
            'adapter'=>\Modules\Symbol\Exchanges\Bybit::class,
            'suffix'=>''
        ],
    ]
];
