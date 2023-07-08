<?php

namespace Modules\Trader\Entities;

use Modules\Settings\Entities\Setting;

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
        private array $coin = [],
    ) {
        $this->symbol = explode(':', $symbol);
        // биржи с лучшим предложением
        $this->setBetterPrices();
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
        $restBaseVolume = $this->buy['volume']['base'];
        $restQuoteVolume = $this->maxVolume;
        $this->buy['volume']['quote'] = 0;
        $this->buy['volume']['base'] = 0;

        foreach ($this->buy['book'] as $book) {
            if ($restBaseVolume > 0 && $restQuoteVolume > 0) {
                $baseVolume = min($restBaseVolume, $book['value']);
                $quoteVolume = min($baseVolume * $book['price'], $restQuoteVolume);

                $baseVolume = $quoteVolume / $book['price'];

                $restQuoteVolume -= $quoteVolume;
                $restBaseVolume -= $baseVolume;

                $this->buy['volume']['quote'] += $quoteVolume;
                $this->buy['volume']['base'] += $baseVolume;
                $this->buy['price']['end'] = $book['price'];
            }
        }
    }

    private function calculatePricesAndVolumesForSellExchange()
    {
        $restBaseVolume = $this->buy['volume']['base'];
        $this->sell['volume']['base'] = 0;
        $this->sell['volume']['quote'] = 0;

        foreach ($this->sell['book'] as $book) {
            if ($restBaseVolume > 0) {
                $baseVolume = min($restBaseVolume, $book['value']);
                $restBaseVolume -= $baseVolume;
                $quoteVolume = $baseVolume * $book['price'];
                $this->sell['volume']['quote'] += $quoteVolume;
                $this->sell['volume']['base'] += $baseVolume;
                $this->sell['price']['end'] = $book['price'];
            }
        }
    }

    private function setBookFromAnotherExchange(string $direction)
    {
        // слияние однонаправленных ордеров выбранных бирж
        $book = array_merge(
            $this->orderBook[$this->{$direction}['exchange']][($direction === 'sell') ? 'bids' : 'asks'],
            $this->orderBook[$this->{str_replace($direction, '',
                'buysell')}['exchange']][($direction === 'sell') ? 'bids' : 'asks']
        );
        // уничтожение одеров, цена которых выходит из диапазона лучших цен
        foreach ($book as $index => $order) {
            $borders = ($direction == 'sell') ?
                [$this->orderBook[$this->buy['exchange']]['asks'][0]['price'],$this->sell['price']['start']] :
                [$this->buy['price']['start'],$this->orderBook[$this->sell['exchange']]['bids'][0]['price']];

            if ($this->inRage($borders,$order['price'])) {
                $this->{$direction}['volume']['base'] += 0.996 * $order['value'];
            } else {
                unset($book[$index]);
            }
        }

        $this->{$direction}['book'] = $book;
    }

    private function setBetterPrices()
    {
        $asks = [];
        $bids = [];
        // заполнение массива ценами первых ордеров с каждой биржы в обеих направлениях
        foreach ($this->orderBook as  $exchange => $book) {
            $check = is_array($this->coin[$exchange]) && (!Setting::env('CHECK_WITHDRAWAL') || $this->coin[$exchange]['status']);

            if ($check) {
                $asks[] = $book['asks'][0]['price'];
                $bids[] = $book['bids'][0]['price'];
            }
        }
        // выбор учшихцен
        $maxAsk = min($asks);
        $minBid = max($bids);
        // поиск бирж, предлогащих лучшие цены
        foreach ($this->orderBook as $exchange => $book) {
            if ($book['asks'][0]['price'] == $maxAsk) {
                $this->buy = [
                    'exchange' => $exchange,
                    'volume' => [
                        'base'=>0,
                        'quote'=>0
                    ],
                    'price' => [
                        'start'=>$book['asks'][0]['price'],
                        'end'=>0
                    ]
                ];
            }

            if ($book['bids'][0]['price'] == $minBid) {
                $this->sell = [
                    'exchange' => $exchange,
                    'price' => [
                        'start'=>$book['bids'][0]['price'],
                        'end'=>0
                    ],
                    'volume' => [
                        'base'=>0,
                        'quote'=>0
                    ]
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
            return [$this->buy['price']['start'], $this->buy['price']['end']];
        }

        return $this->buy['price']['start'].' - '.$this->buy['price']['end'].' '.$this->symbol[0];
    }

    public function baseCoinBuyVolume(bool $withSymbol = true)
    {
        return $this->buy['volume']['base'].($withSymbol ? ' '.$this->symbol[0] : '');
    }

    public function quoteCoinBuyVolume(bool $withSymbol = true)
    {
        return $this->buy['volume']['quote'].($withSymbol ? ' '.$this->symbol[1] : '');
    }

    public function quoteCoinSellVolume(bool $withSymbol = true)
    {
        return $this->sell['volume']['quote'].($withSymbol ? ' '.$this->symbol[1] : '');
    }

    public function baseCoinSellVolume(bool $withSymbol = true)
    {
        return $this->sell['volume']['base'].($withSymbol ? ' '.$this->symbol[0] : '');
    }

    public function quoteCoinSellPrice(bool $isArray = false)
    {
        if ($isArray) {
            return [$this->sell['price']['start'], $this->sell['price']['end']];
        }

        return $this->sell['price']['start'].' - '.$this->sell['price']['end'].' '.$this->symbol[1];
    }

    public function quoteCoinProfit(bool $withSymbol = true): string
    {
        return number_format(($this->quoteCoinSellVolume(false) - $this->quoteCoinBuyVolume(false)),
                8).($withSymbol ? ' '.$this->symbol[1] : '');
    }

    public function spread(): string
    {
        return number_format(($this->sell['price']['start'] - $this->buy['price']['start']) * 100 / $this->sell['price']['start'],3).'%';
    }

    public function relativeProfit(): float
    {
        return ($this->quoteCoinSellVolume(false) == 0) ? -1 : (100 * (float) $this->quoteCoinProfit(false) / $this->quoteCoinSellVolume(false));
    }

    private function pivotToMinBaseVolume()
    {
        $volume = max(min([$this->buy['volume']['base'], $this->sell['volume']['base']]), 0);
        $volumeWithFee = ($this->coin[$this->buy['exchange']]['percent'] ?
            $volume * (1 + $this->coin[$this->buy['exchange']]['fee']):
            $volume - $this->coin[$this->buy['exchange']]['fee']);

        $this->buy['volume']['base'] = $volume;
        $this->sell['volume']['base'] = $volumeWithFee;
    }

    private function inRage(array $borders,float $value): bool
    {
        return (float)$borders[0] <= $value && (float)$borders[1] >= $value;
    }
}
