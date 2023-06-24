<?php

namespace Modules\Settings\Http\Controllers;

abstract class Controller extends \App\Http\Controllers\Controller
{
    public array $fields = [
        'TARGET_PROFIT'=>['Target Profit(%)','input'],
        'CHECK_WITHDRAWAL'=>['Check Withdraw','checkbox'],
        'IS_TRADING_ENABLED'=>['Trading Enabled','checkbox'],
        'LIMIT_ENABLED'=>['Limit Enabled','checkbox']
    ];

    public function __construct()
    {
        foreach (config('symbol.exchanges') as $exchange => $item) {
            $this->fields[] = [
                'title'=>$item['title'],
                'fields'=>[
                    strtoupper($exchange).'_API_KEY'=> 'API Key',
                    strtoupper($exchange).'_API_SECRET'=> 'API Secret'
                ]
            ];
        }
    }
}
