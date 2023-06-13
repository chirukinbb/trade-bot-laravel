<?php

namespace Modules\Trader\Console\Commands;

use Illuminate\Console\Command;
use Modules\Signal\Entities\Deal;
use Modules\Signal\Entities\Signal;
use Modules\Symbol\Exchanges\Binance;
use Modules\Trader\Entities\Trade;
use function config;

class TradeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trader:symbol {symbol} {volume} {proxy_number}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'php artisan trader:symbol';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $start = microtime(true);
        $exchanges = [];
        $tgBot = new \Telegram\Bot\Api(env('TELEGRAM_BOT_TOKEN'));
        $symbol = $this->argument('symbol');
        $volume = $this->argument('volume');
        $number = $this->argument('proxy_number');
        \Log::info($symbol);

        if (is_null($symbol)){
            return 1;
        }

        $book = [];
        $links = [];
        $coin =  [];
        $symbolData = json_decode(\Storage::get('symbols.json'),true);
        $coinData = json_decode(\Storage::get('coins.json'),true);

        foreach (config('symbol.exchanges') as $exchange => $data){
            $exchanges[$exchange] = ['adapter'=>new $data['adapter'](config('symbol.proxies')[$number],$symbolData[$exchange],$coinData[$exchange])];
            $exchanges[$exchange]['is_online'] = $exchanges[$exchange]['adapter']->isSymbolOnline($symbol);
        }

        foreach (config('symbol.exchanges') as $exchange => $data){
            if ($exchanges[$exchange]['is_online']) {
                $book[$exchange] = $exchanges[$exchange]['adapter']->orderBook($symbol);
                $links[$exchange] = $exchanges[$exchange]['adapter']->link($symbol);
                $coin[$exchange] = $exchanges[$exchange]['adapter']->coinInfo(explode(':',$symbol)[0]);
            }
        }

        if (!empty($book)) {
            $trade = new Trade($symbol, $book, $links,$volume,$coin);

            $sell = $trade->sell();
            $buy = $trade->buy();

            if ($trade->relativeProfit() > env('TARGET_PROFIT') && $sell['exchange'] !== $buy['exchange']) {

                if (env('IS_TRADING_ENABLED') == 1) {
                    $sellOrderId = $exchanges[$sell['exchange']]['adapter']->sendOrder($sell);
                    $buyOrderId = $exchanges[$buy['exchange']]['adapter']->sendOrder($buy);
                }

                $signal = Signal::getModel();

                $signal->base_coin = explode(':', $symbol)[0];
                $signal->quote_coin = explode(':', $symbol)[1];
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

        \Log::info($symbol);
        \Log::info(microtime(true) - $start);
    }
}
