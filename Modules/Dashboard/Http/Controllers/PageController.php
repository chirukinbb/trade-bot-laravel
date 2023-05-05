<?php

namespace Modules\Dashboard\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Quotation\Entities\Signal;

class PageController extends Controller
{
    private array $periods = [
        'All time',
        'Year',
        'Month',
        'Week'
    ];

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        $stats = $this->getStats($request->period ?? 0);

        return view('dashboard::index', [
            'periods' => $this->periods,
            'stats' => $stats
        ]);
    }

    private function getStats(int $period)
    {
        $from = match ($period) {
            0 => now()->subCentury(),
            1 => now()->subYear(),
            2 => now()->subMonth(),
            3 => now()->subWeek()
        };
        $stats = [];

        Signal::where('created_at','>',$from)->each(function (Signal $signal) use (&$stats){
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
