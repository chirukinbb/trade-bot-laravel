<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Signal\Entities\Deal;
use Modules\Signal\Entities\Signal;
use Modules\Symbol\Entities\Symbol;
use Modules\Symbol\Exchanges\Binance;
use Modules\Symbol\Exchanges\Exchange;
use Modules\Trader\Entities\Trade;
use React\EventLoop\Factory;
use React\EventLoop\Loop;
use function env;
use function Webmozart\Assert\Tests\StaticAnalysis\float;

class Example2ExchangeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trader:example2';

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
        $binance = new  Binance();

        $time_start0 = microtime(true);
        $mem_start = memory_get_usage();

        $book = $binance->isSymbolOnline('BTC:USDT');


        echo microtime(true) - $time_start0;
        echo '/';
        echo memory_get_usage() - $mem_start;
    }
}
