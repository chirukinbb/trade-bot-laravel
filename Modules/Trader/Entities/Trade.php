<?php

namespace Modules\Trader\Entities;

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
Профит: '.$this->baseCoinProfit().'
Спред: '.$this->spread().'
📤Вывод:
✅ '.$this->buyExchangeTitle(false).' | ✅ '.$this->sellExchangeTitle(false);
    }

    private function calculatePricesAndVolumes(string $direction)
    {
        $restVolume = $this->sell['total']['volume'];
        $this->sell['total']['quote'] = 0;
        $i = 0;

        while ($restVolume > 0){
            if (isset($this->sell['book'][$i])){
                $volume = min($restVolume,$this->sell['book'][$i]['volume']);
                $restVolume -= $volume;
                $this->sell['total']['quote'] += $volume * $this->sell['book'][$i]['price'];
            }

            $i++;
        }

        if (($rest = $this->{$direction}['volume'] - $this->orderBook[$this->{$direction}['exchange']]['asks'][0]['value']) > 0){
            $i = count($this->orderBook[$this->{($direction === 'sell') ? 'buy' : 'sell'}['exchange']]['bids']);

            while ($rest > 0){
                if ($this->orderBook[$this->{($direction === 'sell') ? 'buy' : 'sell'}['exchange']]['bids'][$i]['price'] > $this->{$direction}['price']['start']) {
                    $rest -= $this->orderBook[$this->{($direction === 'sell') ? 'buy' : 'sell'}['exchange']]['bids'][$i]['value'];
                    $this->{$direction}['price']['end'] = $this->orderBook[$this->{($direction === 'sell') ? 'buy' : 'sell'}['exchange']]['bids'][$i]['price'];
                }

                $i++;
            }
        }
    }

    private function setBookFromAnotherExchange(string $direction)
    {
        $this->{$direction}['book'] = array_merge(
            $this->{$direction}['book'],
            $this->orderBook[$this->{$direction}['exchange'][($direction === 'sell') ? 'bids' : 'asks']]
        );
        $this->{$direction}['total'] = [
            'volume'=>0,
            'price'=>['start'=>$this->{$direction}['book'][0]['price']]
        ];

        foreach ($this->{$direction}['book'] as $book) {
            $result = ($direction === 'sell') ? $this->{$direction}['book'][0]['price'] <= $book['price']
                : $this->{$direction}['book'][0]['price'] >= $book['price'];

            if ($result) {
                $this->{$direction}['total']['volume'] += $book['volume'];
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
        return $wrap ? $this->linkWrap('buy',config('symbol.exchanges.'.$this->buy['exchange'].'.label'))
            : config('symbol.exchanges.'.$this->buy['exchange'].'.label');
    }

    private function sellExchangeTitle(bool $wrap = true)
    {
        return $wrap ? $this->linkWrap('sell',config('symbol.exchanges.'.$this->buy['exchange'].'.label'))
            : config('symbol.exchanges.'.$this->sell['exchange'].'.label');
    }

    private function linkWrap(string $direction, string $anchor)
    {
        $key = $this->{$direction}['exchange'];

        return '<a href="'.$this->links[$key].'">'.$anchor.'</a>';
    }

    private function baseCoinBuyPrice()
    {
        return $this->buy['total']['price']['start'].' - '.$this->buy['total']['price']['end'].$this->symbol[0];
    }

    private function baseCoinBuyVolume(bool $withSymbol = true)
    {
        return $this->buy['total']['volume'].($withSymbol ? $this->symbol[1] : '');
    }

    private function quoteCoinBuyVolume(bool $withSymbol = true)
    {
        return $this->buy['total']['quote'].($withSymbol ? $this->symbol[1] : '');
    }

    private function quoteCoinSellVolume(bool $withSymbol = true)
    {
        return $this->sell['total']['quote'].($withSymbol ? $this->symbol[1] : '');
    }

    private function baseCoinSellVolume(bool $withSymbol = true)
    {
        return $this->sell['total']['volume'].($withSymbol ? $this->symbol[1] : '');
    }

    private function quoteCoinSellPrice()
    {
        return $this->buy['total']['price']['start'].' - '.$this->buy['total']['price']['end'].$this->symbol[0];
    }

    private function baseCoinProfit()
    {
        return ($this->quoteCoinBuyVolume(false) - $this->quoteCoinSellPrice()).$this->symbol[1];
    }

    private function spread()
    {
        return ($this->quoteCoinBuyVolume(false) - $this->quoteCoinSellVolume(false)) * 100 / $this->quoteCoinSellVolume(false);
    }
}
