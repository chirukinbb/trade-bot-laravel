<?php

namespace Modules\Trader\Console\Commands;

use Illuminate\Console\Command;
use Lin\Binance\Binance;
use Lin\Bitget\BitgetSpot;
use Lin\Bybit\BybitSpot;
use Lin\Gate\GateSpot;
use Lin\Huobi\HuobiSpot;
use Lin\Ku\Kucoin;
use Lin\Mxc\MxcSpot;
use Lin\Okex\OkexSpot;
use Modules\Symbol\Entities\Symbol;
use function config;

class ExchangeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trader:exchanges';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Main robot command';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Symbol::each(function (Symbol $symbol) use (&$onlineSymbols){
            foreach (config('symbol.exchanges') as $exchange => $data){
                $onlineSymbols[] = 0;
            }
        });
    }
}
