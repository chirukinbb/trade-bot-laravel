<?php

namespace Modules\Signal\Repositories;

use Modules\Signal\Entities\Signal;

class SignalRepository
{
    private Signal $signal;

    public function __construct()
    {
        $this->signal = Signal::getModel();
    }

    public function get(int $id)
    {
        return $this->signal->clone()->find($id);
    }

    public function paginator(string|null $symbol)
    {
        return (is_null($symbol) || $symbol === 'all') ? $this->signal->clone()->paginate()
            : $this->signal->clone()->where(\DB::raw('concat(`base_coin`,":",`quote_coin`)'),$symbol)->paginate();
    }

    public function symbols()
    {
        $symbols = [];

        Signal::select(\DB::raw('concat(`base_coin`,":",`quote_coin`) as name'))
            ->groupBy(\DB::raw('concat(`base_coin`,":",`quote_coin`)'))
            ->each(function (Signal $signal) use (&$symbols){
                $symbols[] = $signal->name;
            });

        return $symbols;
    }

    public function getStats(int $period)
    {
        $from = match ($period) {
            0 => now()->subCentury(),
            1 => now()->subYear(),
            2 => now()->subMonth(),
            3 => now()->subWeek()
        };
        $stats = [];

        $this->signal->clone()->where('created_at','>',$from)->each(function (Signal $signal) use (&$stats){
            $stats[$signal->base_coin.':'.$signal->quote_coin]['volume'][] = $signal->buy_volumes[0];
            $stats[$signal->base_coin.':'.$signal->quote_coin]['profit'][] = $signal->profit(false);
            $stats[$signal->base_coin.':'.$signal->quote_coin]['coin'] = [
                'base'=>$signal->base_coin,
                'quote'=>$signal->quote_coin
            ];
        });

        foreach ($stats as &$stat){
            $stat = [
                'count'=>count($stat['volume']),
                'volume'=>[
                    'avg'=>array_sum($stat['volume'])/count($stat['volume']).$stat['coin']['base'],
                    'max'=>max($stat['volume']).$stat['coin']['base']
                ],
                'profit'=>[
                    'sum'=>number_format(array_sum($stat['profit']),8).$stat['coin']['quote'],
                    'max'=>max($stat['profit']).$stat['coin']['quote']
                ]
            ];
        }

        return $stats;
    }
}
