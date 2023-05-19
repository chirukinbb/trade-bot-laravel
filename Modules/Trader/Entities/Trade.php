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
        private array $links,
        float $fee = 0
    )
    {
        $this->symbol = explode(':',$symbol);
        // биржи с лучшим предложением
        $this->setBetterPrices();
        // установка книги ордеров с противоположной биржы
        $this->setBookFromAnotherExchange('sell');
        $this->setBookFromAnotherExchange('buy');
        // сведение к минимальному обьему
        $this->buy['total']['volume'] = min($this->buy['total']['volume'], $this->sell['total']['volume']) - $fee;
        $this->sell['total']['volume'] = min($this->buy['total']['volume'], $this->sell['total']['volume']) - $fee;

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
            array_reverse($this->orderBook[$this->{str_replace($direction,'','buysell')}['exchange']][($direction !== 'sell') ? 'bids' : 'asks'])
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

    private function setBetterPrices()
    {
        $asks = [];
        $bids =  [];

        foreach ($this->orderBook as $exchange => $book){
            $asks[] = $book['asks'][0]['price'];
            $bids[] = $book['bids'][0]['price'];
        }

        $minAsk = min($asks);
        $maxBid = max($bids);

        foreach ($this->orderBook as $exchange => $book){
            if ($book['asks'][0]['price'] === $minAsk){
                $this->sell = [
                    'exchange' => $exchange,
                    'book' => [$book['asks'][0]]
                ];
            }

            if ($book['bids'][0]['price'] === $maxBid){
                $this->buy = [
                    'exchange' => $exchange,
                    'book' => [$book['bids'][0]]
                ];
            }
        }
    }

    function buyExchangeTitle(bool $wrap = true)
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
            return [$this->sell['total']['price']['start'],$this->sell['total']['price']['end']];
        }

        return $this->sell['total']['price']['start'].' - '.$this->sell['total']['price']['end'].' '.$this->symbol[1];
    }

    public function quoteCoinProfit(): string
    {
        return number_format(($this->quoteCoinSellVolume(false) - $this->quoteCoinBuyVolume(false)),8).' '.$this->symbol[1];
    }

    public function spread(): string
    {
        return number_format(($this->sell['book'][0]['price'] - $this->buy['book'][0]['price']) * 100 / $this->sell['book'][0]['price'],3).'%';
    }

    public function relativeProfit(): float
    {
        return (100*$this->quoteCoinProfit() / $this->quoteCoinSellVolume());
    }
}
