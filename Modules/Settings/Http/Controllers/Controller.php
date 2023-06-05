<?php

namespace Modules\Settings\Http\Controllers;

abstract class Controller extends \App\Http\Controllers\Controller
{
    protected array $fields = [
        'TARGET_PROFIT'=>'Target Profit(%)'
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
