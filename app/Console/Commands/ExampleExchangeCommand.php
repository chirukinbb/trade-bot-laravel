<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Signal\Entities\Deal;
use Modules\Signal\Entities\Signal;
use Modules\Symbol\Entities\Symbol;
use Modules\Symbol\Exchanges\Exchange;
use Modules\Trader\Entities\Trade;
use React\EventLoop\Loop;
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
        $tgBot = new \Telegram\Bot\Api(env('TELEGRAM_BOT_TOKEN'));

        foreach (config('symbol.exchanges') as $exchange => $data){
            $exchanges[$exchange] = ['adapter'=>new $data['adapter']];
        }

        Symbol::each(function (Symbol $symbol) use ($exchanges,$tgBot){
            $book = [];
            $links = [];

            try {
                $book = json_decode(file_get_contents(storage_path('book.json')),true);
                $links = json_decode(file_get_contents(storage_path('links.json')),true);

                $trade = new Trade($symbol->name, $book,$links);

                if ($trade->spread() > env('TARGET_SPREAD')) {

                    if (env('IS_TRADING_ENABLED') == 1){
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

                    if (env('IS_TRADING_ENABLED') == 1){
                        Deal::create([
                            'exchange'=>$sell['exchange'],
                            'exchange_id'=>$sellOrderId,
                            'signal_id'=>$signal->id
                        ]);
                        Deal::create([
                            'exchange'=>$buy['exchange'],
                            'exchange_id'=>$buyOrderId,
                            'signal_id'=>$signal->id
                        ]);
                    }

                    $tgBot->sendMessage([
                        'chat_id' => env('TELEGRAM_CHAT_ID'),
                        'text' => $trade->message(),
                        'parse_mode' => 'HTML'
                    ]);
                }
            }catch (\Exception $exception){
                \Log::info($exception->getMessage());
                dd($exception->getLine());
            }
        });
    }
}
