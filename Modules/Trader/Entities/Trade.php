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
        private float $maxVolume = 0,
        private float $fee = 0,
    ) {
        $this->symbol = explode(':', $symbol);
        // биржи с лучшим предложением
        $this->setBetterPrices();
        //dump($this->orderBook,$this->sell,$this->buy);
        // установка книги ордеров с противоположной биржы
        $this->setBookFromAnotherExchange('sell');
        $this->setBookFromAnotherExchange('buy');
        // сведение к минимальному обьему
        $this->pivotToMinBaseVolume();

        $this->calculatePricesAndVolumesForBuyExchange();
        // сведение к минимальному обьему
        $this->pivotToMinBaseVolume();

        $this->calculatePricesAndVolumesForSellExchange();
    }

    public function message()
    {
        return $this->buyExchangeTitle().' -> '.$this->sellExchangeTitle().' | '.implode('/', $this->symbol).'
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

    private function calculatePricesAndVolumesForBuyExchange()
    {
        // обьем, который нужно сьесть
        $restBaseVolume = $this->buy['total']['volume'];
        $restQuoteVolume = $this->maxVolume;
        $this->buy['total']['quote'] = 0;
        $this->buy['total']['volume'] = - $this->fee;
        // расчет конечной цены
        // действие повторяетчя пока не сьест ввесь обьем
        foreach ($this->buy['book'] as $book) {
            if ($restBaseVolume > 0 && $restQuoteVolume > 0) {
                // сравнивает оставшийся обьем и обьем текущего ордера(base coin), берет меньший
                $baseVolume = min([$restBaseVolume, $book['value']]);
                // считает обьем quote coin в сьедаемом обьеме ордера и сравниваем с лимитом
                $quoteVolume = min($baseVolume * $book['price'], $restQuoteVolume);
                // если обьем второго коина меньше - пересчитать базовую
                if ($restQuoteVolume === $quoteVolume) {
                    $baseVolume = $quoteVolume / $book['price'];
                }
                // оставшийся обьем второго коина
                $restQuoteVolume -= $quoteVolume;
                // вычитает выбранный обьем из осавшегося(база-коин)
                $restBaseVolume -= $baseVolume;
                // купленный квот-коин
                $this->buy['total']['quote'] += $quoteVolume;
                // обменянный база-коин
                $this->buy['total']['volume'] += $baseVolume;
                // записывает(перезаписывает) цену текущего ордера как конечную цену
                $this->buy['total']['price']['end'] = $book['price'];
            }
        }
    }

    private function calculatePricesAndVolumesForSellExchange()
    {
        // обьем, который нужно сьесть
        $restBaseVolume = $this->sell['total']['volume'];
        $this->sell['total']['quote'] = 0;
        $this->sell['total']['volume'] = 0;
        // расчет конечной цены
        // действие повторяетчя пока не сьест ввесь обьем
        foreach ($this->sell['book'] as $book) {
            if ($restBaseVolume > 0) {
                // сравнивает оставшийся обьем и обьем текущего ордера(base coin), берет меньший
                $baseVolume = min([$restBaseVolume, $book['value']]);
                // обменянный база-коин
                $this->sell['total']['volume'] += $baseVolume;
                // вычитает выбранный обьем из осавшегося(база-коин)
                $restBaseVolume -= $baseVolume;
                // считает обьем quote coin в сьедаемом обьеме ордера и сравниваем с лимитом
                $quoteVolume = $baseVolume * $book['price'];
                // купленный квот-коин
                $this->sell['total']['quote'] += $quoteVolume;
                // обменянный база-коин
                $this->sell['total']['volume'] += $baseVolume;
                // записывает(перезаписывает) цену текущего ордера как конечную цену
                $this->sell['total']['price']['end'] = $book['price'];
            }
        }
    }

    private function setBookFromAnotherExchange(string $direction)
    {
        // слияние однонаправленных ордеров выбранных бирж
        // array_reverse розворачивает массив ордеров в порядке от более к менее удаленной спотовой цене
        $this->{$direction}['book'] = array_merge(
            $this->{$direction}['book'],
            array_reverse($this->orderBook[$this->{str_replace($direction, '',
                'buysell')}['exchange']][($direction === 'sell') ? 'bids' : 'asks'])
        );
        // установка стартовой цены для биржы
        $this->{$direction}['total'] = [
            'volume' => 0,
            'price' => [
                'start' => $this->{$direction}['book'][0]['price'],
                'end' => $this->{$direction}['book'][0]['price'],
            ]
        ];
        // уничтожение одеров, цена которых выходит из диапазона лучших цен
        foreach ($this->{$direction}['book'] as $book) {
            $borders = ($direction == 'sell') ?
                [$this->orderBook[$this->buy['exchange']]['asks'][0]['price'],$this->sell['book'][0]['price']] :
                [$this->buy['book'][0]['price'],$this->orderBook[$this->sell['exchange']]['bids'][0]['price']];


            if ($this->inRage($borders,$book['price'])) {
                $this->{$direction}['total']['volume'] += 0.996 * $book['value'];
            } else {
                $index = array_search($book, $this->{$direction}['book']);

                if ($index > 0) {
                    unset($this->{$direction}['book'][$index]);
                }
            }
        }
    }

    private function setBetterPrices()
    {
        $asks = [];
        $bids = [];
        // заполнение массива ценами первых ордеров с каждой биржы в обеих направлениях
        foreach ($this->orderBook as  $book) {
            $asks[] = $book['asks'][0]['price'];
            $bids[] = $book['bids'][0]['price'];
        }
        // выбор учшихцен
        $maxAsk = min($asks);
        $minBid = max($bids);
        // поиск бирж, предлогащих лучшие цены
        foreach ($this->orderBook as $exchange => $book) {
            if ($book['asks'][0]['price'] == $maxAsk) {
                $this->buy = [
                    'exchange' => $exchange,
                    'book' => [$book['asks'][0]]
                ];
            }

            if ($book['bids'][0]['price'] == $minBid) {
                $this->sell = [
                    'exchange' => $exchange,
                    'book' => [$book['bids'][0]]
                ];
            }
        }
    }

    function buyExchangeTitle(bool $wrap = true)
    {
        return $wrap ? $this->linkWrap('buy', config('symbol.exchanges.'.$this->buy['exchange'].'.title'))
            : config('symbol.exchanges.'.$this->buy['exchange'].'.title');
    }

    private function sellExchangeTitle(bool $wrap = true)
    {
        return $wrap ? $this->linkWrap('sell', config('symbol.exchanges.'.$this->sell['exchange'].'.title'))
            : config('symbol.exchanges.'.$this->sell['exchange'].'.title');
    }

    private function linkWrap(string $direction, string $anchor)
    {
        $key = $this->{$direction}['exchange'];

        return '<a href="'.$this->links[$key].'">'.$anchor.'</a>';
    }

    public function baseCoinBuyPrice(bool $isArray = false)
    {
        if ($isArray) {
            return [$this->buy['total']['price']['start'], $this->buy['total']['price']['end']];
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
        if ($isArray) {
            return [$this->sell['total']['price']['start'], $this->sell['total']['price']['end']];
        }

        return $this->sell['total']['price']['start'].' - '.$this->sell['total']['price']['end'].' '.$this->symbol[1];
    }

    public function quoteCoinProfit(bool $withSymbol = true): string
    {
        return number_format(($this->quoteCoinSellVolume(false) - $this->quoteCoinBuyVolume(false)),
                8).($withSymbol ? ' '.$this->symbol[1] : '');
    }

    public function spread(): string
    {
        return ($this->sell['book'][0]['price'] === 0 ? 0 : number_format(($this->sell['book'][0]['price'] - $this->buy['book'][0]['price']) * 100 / $this->sell['book'][0]['price'],
                3)).'%';
    }

    public function relativeProfit(): float
    {
        return ($this->quoteCoinSellVolume(false) == 0) ? -1 : (100 * (float) $this->quoteCoinProfit(false) / $this->quoteCoinSellVolume(false));
    }

    private function pivotToMinBaseVolume()
    {
        $volume = max(min([$this->buy['total']['volume'], $this->sell['total']['volume']]), 0);

        $this->buy['total']['volume'] = $volume;
        $this->sell['total']['volume'] = $volume;
    }

    private function inRage(array $borders,float $value): bool
    {
        return $borders[0] <= $value && $borders[1] >= $value;
    }
}
