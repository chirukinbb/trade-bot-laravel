<?php

namespace Modules\Trader\Console\Commands;

use Illuminate\Console\Command;
use Modules\Signal\Entities\Deal;
use Modules\Signal\Entities\Signal;
use Modules\Symbol\Entities\Symbol;
use Modules\Trader\Entities\Trade;
use React\EventLoop\Factory;
use React\EventLoop\Loop;
use React\Promise\Deferred;
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
    protected $description = ' robot command';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $exchanges = [];
        $tgBot = new \Telegram\Bot\Api(env('TELEGRAM_BOT_TOKEN'));
        $loop = Loop::get();

        foreach (config('symbol.exchanges') as $exchange => $data){
            $exchanges[$exchange] = ['adapter'=>new $data['adapter']];
        }

        Symbol::limit(10)->each(function (Symbol $symbol) use ($exchanges,&$loop,$tgBot){
            $loop->addPeriodicTimer(env('SECONDS_TIMEOUT'),function () use ($exchanges,$tgBot,$symbol){
                $book = [];
                $links = [];

                foreach (config('symbol.exchanges') as $exchange => $data){
                    $exchanges[$exchange]['is_online'] = $exchanges[$exchange]['adapter']->isSymbolOnline($symbol->name);
                }

                foreach (config('symbol.exchanges') as $exchange => $data){
                    if ($exchanges[$exchange]['is_online']) {
                        $book[$exchange] = $exchanges[$exchange]['adapter']->orderBook($symbol->name);
                        $links[$exchange] = $exchanges[$exchange]['adapter']->link($symbol->name);
                    }
                }

                if (!empty($book)) {
                    $trade = new Trade($symbol->name, $book, $links,
                        $exchanges['binance']['adapter']->withdrawalFee(explode(':', $symbol->name)[1]),$symbol->volume);

                    if ($trade->relativeProfit() > env('TARGET_PROFIT')) {

                        if (env('IS_TRADING_ENABLED') == 1) {
                            $sell = $trade->sell();
                            $buy = $trade->buy();
                            $sellOrderId = $exchanges[$sell['exchange']]['adapter']->sendOrder($sell);
                            $buyOrderId = $exchanges[$buy['exchange']]['adapter']->sendOrder($buy);
                        }

                        $signal = Signal::getModel();

                        $signal->base_coin = explode(':', $symbol->name)[0];
                        $signal->quote_coin = explode(':', $symbol->name)[1];
                        $signal->buy_prices = $trade->baseCoinBuyPrice(true);
                        $signal->sell_prices = $trade->quoteCoinSellPrice(true);
                        $signal->sell_volumes = [$trade->baseCoinSellVolume(false), $trade->quoteCoinSellVolume(false)];
                        $signal->buy_volumes = [$trade->baseCoinBuyVolume(false), $trade->quoteCoinBuyVolume(false)];
                        $signal->buy_exchange = $trade->buy()['exchange'];
                        $signal->sell_exchange = $trade->sell()['exchange'];

                        $signal->save();

                        if (env('IS_TRADING_ENABLED') == 1) {
                            Deal::create([
                                'exchange' => $sell['exchange'],
                                'exchange_id' => $sellOrderId,
                                'signal_id' => $signal->id
                            ]);
                            Deal::create([
                                'exchange' => $buy['exchange'],
                                'exchange_id' => $buyOrderId,
                                'signal_id' => $signal->id
                            ]);
                        }

                        $tgBot->sendMessage([
                            'chat_id' => env('TELEGRAM_CHAT_ID'),
                            'text' => $trade->message(),
                            'parse_mode' => 'HTML'
                        ]);
                    }
                }
            });
        });

        $loop->run();
    }
}
