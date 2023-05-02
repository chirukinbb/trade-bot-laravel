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
use Modules\Symbol\Exchanges\Exchange;
use Modules\Trader\Entities\Trade;
use React\EventLoop\Loop;
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
        $exchanges = [];
        $tgBot = new \Telegram\Bot\Api(env('TELEGRAM_BOT_TOKEN'));

        foreach (config('symbol.exchanges') as $exchange => $data){
            $exchanges[$exchange] = new $data['adapter'];
        }

        Loop::addTimer(1,function ()use ($exchanges,$tgBot){
            Symbol::each(function (Symbol $symbol) use ($exchanges,$tgBot){
                $book = [];

                foreach (config('symbol.exchanges') as $exchange => $data){
                    /**
                     * @var Exchange $exchanges[$exchange]
                     */
                    if ($exchanges[$exchange]->isSymbolOnline($symbol->name)){
                        $book[$exchange] = $exchanges[$exchange]->orderBook($symbol->name);
                    }
                }

                $trade = new Trade($symbol, $book);

                $tgBot->sendMessage([
                    'chat_id'=>env('TELEGRAM_CHAT_ID'),
                    'text'=>$trade->message()
                ]);
            });
        });
    }
}
