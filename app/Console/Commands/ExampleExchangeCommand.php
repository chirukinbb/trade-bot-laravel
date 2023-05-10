<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Symbol\Exchanges\Exchange;
use Modules\Trader\Entities\Trade;
use function env;

class ExampleExchangeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trader:example';

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
        $exchanges = [];
        ;$symbol='BTC:USDT';

        foreach (config('symbol.exchanges') as $exchange => $data){
            $exchanges[$exchange] = new $data['adapter'];
        }

        foreach (config('symbol.exchanges') as $exchange => $data){
            /**
             * @var Exchange $exchanges[$exchange]
             */
            if ($exchanges[$exchange]->isSymbolOnline($symbol)){
                $book[$exchange] = $exchanges[$exchange]->orderBook($symbol);
                $links[$exchange] = $exchanges[$exchange]->link($symbol);
            }
        }

        $trade = new Trade('TCC:USD', $book,$links);
    }
}
