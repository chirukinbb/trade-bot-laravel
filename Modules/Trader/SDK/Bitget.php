<?php

namespace Modules\Trader\SDK;

use Lin\Bitget\BitgetSpot;

class Bitget extends BitgetSpot
{
    public function setProxy(array $proxy)
    {
        $this->setOptions(['proxy'=>$proxy]);
    }

    public function clearProxy()
    {
        $this->setOptions(['proxy'=>[]]);
    }
}
