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
        float $fee = 0,
        float $maxVolume = 0,
    )
    {
        $this->symbol = explode(':',$symbol);
        // биржи с лучшим предложением
        $this->setBetterPrices();
        // установка книги ордеров с противоположной биржы
        $this->setBookFromAnotherExchange('sell');
        $this->setBookFromAnotherExchange('buy');
        // сведение к минимальному обьему
        $volume = max(min([$this->buy['total']['volume'], $this->sell['total']['volume']]) - $fee,0);
        $volume = min($volume,$maxVolume);

        $this->buy['total']['volume'] = $volume;
        $this->sell['total']['volume'] = $volume;

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
        // обьем, который нужно сьесть
        $restVolume = $this->{$direction}['total']['volume'];
        $this->{$direction}['total']['quote'] = 0;
        // расчет конечной цены
        // действие повторяетчя пока не сьест ввесь обьем
        foreach ($this->{$direction}['book'] as $book) {
            if ($restVolume > 0){
                // сравнивает оставшийся обьем и обьем текущего ордера, берет меньший
                $volume = min([$restVolume,$book['value']]);
                // вычитает выбранный обьем из осавшегося
                $restVolume -= $volume;
                // считает обьем quote coin в сьеденом обьеме ордера
                $this->{$direction}['total']['quote'] += $volume * $book['price'];
                // записывает(перезаписывает) цену текущего ордера как конечную цену
                $this->{$direction}['total']['price']['end'] = $book['price'];
            }
        }
    }

    private function setBookFromAnotherExchange(string $direction)
    {
        // слияние однонаправленных ордеров выбранных бирж
        // array_reverse розворачивает массив ордеров в порядке от более к менее удаленной спотовой цене
        $this->{$direction}['book'] = array_merge(
            $this->{$direction}['book'],
            array_reverse($this->orderBook[$this->{str_replace($direction,'','buysell')}['exchange']][($direction !== 'sell') ? 'bids' : 'asks'])
        );
        // установка стартовой цены для биржы
        $this->{$direction}['total'] = [
            'volume'=>0,
            'price'=>[
                'start'=>$this->{$direction}['book'][0]['price'],
                'end'=>$this->{$direction}['book'][0]['price'],
                ]
        ];
        // уничтожение одеров, цена которых выходит из диапазона лучших цен
        foreach ($this->{$direction}['book'] as $book) {
            $result = ($direction === 'sell') ? ($this->sell['book'][0]['price'] >= $book['price'] && $this->orderBook[$this->buy['exchange']]['asks'][0]['price'] < $book['price'])
                : (($this->buy['book'][0]['price'] <= $book['price'] && $this->orderBook[$this->sell['exchange']]['bids'][0]['price'] > $book['price']));

            if ($result) {
                $this->{$direction}['total']['volume'] += 0.996 * $book['value'];
            }else{
                $index = array_search($book,$this->{$direction}['book']);

                if ($index > 0)
                unset($this->{$direction}['book'][$index]);
            }
        }
    }

    private function setBetterPrices()
    {
        $asks = [];
        $bids =  [];
        // заполнение массива ценами первых ордеров с каждой биржы в обеих направлениях
        foreach ($this->orderBook as $exchange => $book){
            $asks[] = $book['asks'][0]['price'];
            $bids[] = $book['bids'][0]['price'];
        }
        // выбор учшихцен
        $maxAsk = max($asks);
        $minBid = min($bids);
        // поиск бирж, предлогащих лучшие цены
        foreach ($this->orderBook as $exchange => $book){
            if ($book['asks'][0]['price'] == $maxAsk){
                $this->sell = [
                    'exchange' => $exchange,
                    'book' => [$book['asks'][0]]
                ];
            }

            if ($book['bids'][0]['price'] == $minBid){
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

    public function quoteCoinProfit(bool $withSymbol = true): string
    {
        return number_format(($this->quoteCoinSellVolume(false) - $this->quoteCoinBuyVolume(false)),8).($withSymbol ? ' '.$this->symbol[1] : '');
    }

    public function spread(): string
    {
        return ($this->sell['book'][0]['price'] === 0 ? 0 : number_format(($this->sell['book'][0]['price'] - $this->buy['book'][0]['price']) * 100 / $this->sell['book'][0]['price'],3)).'%';
    }

    public function relativeProfit(): float
    {
        return ($this->quoteCoinSellVolume(false) == 0) ? -1 : (100*(float)$this->quoteCoinProfit(false) / $this->quoteCoinSellVolume(false));
    }
}
