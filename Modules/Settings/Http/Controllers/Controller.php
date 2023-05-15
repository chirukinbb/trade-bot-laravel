<?php

namespace Modules\Settings\Http\Controllers;

abstract class Controller extends \App\Http\Controllers\Controller
{
    protected array $fields = [
        'SECONDS_TIMEOUT'=>'Loop Timeout(seconds)',
        'TARGET_SPREAD'=>'Target Spread(%)'
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
