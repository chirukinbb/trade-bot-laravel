<?php

namespace Modules\Trader\Entities;

use function Webmozart\Assert\Tests\StaticAnalysis\object;

class Trade
{
    private array $sell;
    private array $buy;
    private array $symbol;

    public function __construct(
        string $symbol,
        private array $orderBook,
        private array $links
    )
    {
        $this->symbol = explode(':',$symbol);
        // биржи с лучшим предложением
        foreach ($this->orderBook as $exchange => $book){
            $this->setBetterPrice($exchange,$book,'sell');
            $this->setBetterPrice($exchange,$book,'buy');
        }
        // установка книги ордеров с противоположной биржы
        $this->setBookFromAnotherExchange('sell');
        $this->setBookFromAnotherExchange('buy');
        // сведение к минимальному обьему
        $this->buy['total']['volume'] = min($this->buy['total']['volume'], $this->sell['total']['volume']);
        $this->sell['total']['volume'] = min($this->buy['total']['volume'], $this->sell['total']['volume']);

        $this->calculatePricesAndVolumes('sell');
        $this->calculatePricesAndVolumes('buy');
    }

    public function message()
    {
        return $this->buyExchangeTitle().' -> '.$this->sellExchangeTitle().' | '.implode('/',$this->symbol).'
📉Покупка:
Объем: '.$this->quoteCoinBuyVolume().' -> '.$this->baseCoinBuyVolume().'
Цена: '.$this->baseCoinBuyPrice().'
📈Продажа:
Объем: '.$this->baseCoinSellVolume().' -> '.$this->quoteCoinSellVolume().'
Цена: '.$this->quoteCoinSellPrice().'
Профит: '.$this->quoteCoinProfit().'
Спред: '.$this->spread().'
📤Вывод:
✅ '.$this->buyExchangeTitle(false).' | ✅ '.$this->sellExchangeTitle(false);
    }

    public function buy()
    {
        return $this->buy;
    }

    public function sell()
    {
        return $this->sell;
    }

    private function calculatePricesAndVolumes(string $direction)
    {
        $restVolume = $this->{$direction}['total']['volume'];
        $this->{$direction}['total']['quote'] = 0;
        $i = 0;

        while ($restVolume > 0){
            if (isset($this->{$direction}['book'][$i])){
                $volume = min($restVolume,$this->{$direction}['book'][$i]['value']);
                $restVolume -= $volume;
                $this->{$direction}['total']['quote'] += $volume * $this->{$direction}['book'][$i]['price'];
                $this->{$direction}['total']['price']['end'] = $this->{$direction}['book'][$i]['price'];
            }

            $i++;
        }
    }

    private function setBookFromAnotherExchange(string $direction)
    {
        $this->{$direction}['book'] = array_merge(
            $this->{$direction}['book'],
            $this->orderBook[$this->{$direction}['exchange']][($direction === 'sell') ? 'bids' : 'asks']
        );
        $this->{$direction}['total'] = [
            'volume'=>0,
            'price'=>['start'=>$this->{$direction}['book'][0]['price']]
        ];

        foreach ($this->{$direction}['book'] as $book) {
            $result = ($direction === 'sell') ? ($this->{$direction}['book'][0]['price'] >= $book['price'] && $this->{str_replace($direction,'','buysell')}['book'][0]['price'] < $book['price'])
                : ($this->{$direction}['book'][0]['price'] <= $book['price'] && $this->{str_replace($direction,'','buysell')}['book'][0]['price'] > $book['price']);

            if ($result) {
                $this->{$direction}['total']['volume'] += 0.996 * $book['value'];
            }else{
                $index = array_search($book,$this->{$direction}['book']);
                unset($this->{$direction}['book'][$index]);
            }
        }
    }

    private function setBetterPrice(string $exchange,array$book,string $direction)
    {
        if (!isset($this->{$direction}['book'])){
            $this->{$direction} = [
                'exchange' => $exchange,
                'book' => [$book[($direction === 'sell') ? 'asks' : 'bids'][0]]
            ];
        }else {
            $result = ($direction === 'sell') ? $this->sell['book'][0]['price'] < $book['asks'][0]['price']
                : $this->buy['book'][0]['price'] > $book['bids'][0]['price'];

            if ($result) {
                $this->{$direction} = [
                    'exchange' => $exchange,
                    'book' => [$book[($direction === 'sell') ? 'asks' : 'bids'][0]]
                ];
            }
        }
    }

    private function buyExchangeTitle(bool $wrap = true)
    {
        return $wrap ? $this->linkWrap('buy',config('symbol.exchanges.'.$this->buy['exchange'].'.title'))
            : config('symbol.exchanges.'.$this->buy['exchange'].'.title');
    }

    private function sellExchangeTitle(bool $wrap = true)
    {
        return $wrap ? $this->linkWrap('sell',config('symbol.exchanges.'.$this->sell['exchange'].'.title'))
            : config('symbol.exchanges.'.$this->sell['exchange'].'.title');
    }

    private function linkWrap(string $direction, string $anchor)
    {
        $key = $this->{$direction}['exchange'];

        return '<a href="'.$this->links[$key].'">'.$anchor.'</a>';
    }

    public function baseCoinBuyPrice(bool $isArray = false)
    {
        if ($isArray){
            return [$this->buy['total']['price']['start'],$this->buy['total']['price']['end']];
        }

        return $this->buy['total']['price']['start'].' - '.$this->buy['total']['price']['end'].' '.$this->symbol[0];
    }

    public function baseCoinBuyVolume(bool $withSymbol = true)
    {
        return $this->buy['total']['volume'].($withSymbol ? ' '.$this->symbol[0] : '');
    }

    public function quoteCoinBuyVolume(bool $withSymbol = true)
    {
        return $this->buy['total']['quote'].($withSymbol ? ' '.$this->symbol[1] : '');
    }

    public function quoteCoinSellVolume(bool $withSymbol = true)
    {
        return $this->sell['total']['quote'].($withSymbol ? ' '.$this->symbol[1] : '');
    }

    public function baseCoinSellVolume(bool $withSymbol = true)
    {
        return $this->sell['total']['volume'].($withSymbol ? ' '.$this->symbol[0] : '');
    }

    public function quoteCoinSellPrice(bool $isArray = false)
    {
        if ($isArray){
            return [$this->buy['total']['price']['start'],$this->buy['total']['price']['end']];
        }

        return $this->buy['total']['price']['start'].' - '.$this->buy['total']['price']['end'].' '.$this->symbol[1];
    }

    public function quoteCoinProfit()
    {
        return number_format(($this->quoteCoinSellVolume(false) - $this->quoteCoinBuyVolume(false)),8).' '.$this->symbol[1];
    }

    public function spread()
    {
        return number_format(($this->sell['book'][0]['price'] - $this->buy['book'][0]['price']) * 100 / $this->sell['book'][0]['price'],3).'%';
    }
}
