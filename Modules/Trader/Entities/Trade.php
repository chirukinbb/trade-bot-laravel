<?php

namespace Modules\Trader\Entities;

class Trade
{
    public function __construct(
        private string $symbol,
        private array $orderBook
    )
    {
        print_r($this->orderBook);die;
    }

    public function message()
    {
        return '
'.$this->buyExchangeTitle().' -> '.$this->sellExchangeTitle().' | '.$this->symbol.'
📉Покупка:
Объем: '.$this->quoteCoinVolume().' -> '.$this->baseCoinVolume().'
Цена: 1.14766-1.14879$
📈Продажа:
Объем: 13129 XTZ -> 16783.44 USDT
Цена: 1.358-1.2052$
Профит: 1708.72 USDT
Спред: 11.34%
📤Вывод:
✅ Mexc | ✅ Bybit
        ';
    }

    private function buyExchangeTitle()
    {
        return config('symbol.exchanges.'.$this->buy[0].'.label');
    }

    private function sellExchangeTitle()
    {
    }

    private function quoteCoinVolume()
    {
    }

    private function baseCoinVolume()
    {
    }
}
