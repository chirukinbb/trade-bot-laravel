<?php

namespace Modules\Trader\SDK;

use Lin\Mxc\Request;

class MxcWallet extends Request
{
    public function getCoins()
    {
        $this->host = 'https://api.mexc.com';
        $this->version = 'v3';
        $this->type='GET';
        $this->path='/api/v3/capital/config/getall';
        return $this->exec();
    }
}
