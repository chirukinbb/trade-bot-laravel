<?php

namespace Modules\Trader\SDK;

use Binance\API;

class Binance extends API
{
    public function clearProxy()
    {
        $this->proxyConf = null;
    }
}
