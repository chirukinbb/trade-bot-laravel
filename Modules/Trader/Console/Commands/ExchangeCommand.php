<?php

namespace Modules\Trader\Console\Commands;

use Illuminate\Console\Command;
use Modules\Symbol\Entities\Symbol;
use React\EventLoop\Loop;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

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
    protected $description = 'php artisan trader:exchanges';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $loop = Loop::get();

        $phpBinaryFinder = new PhpExecutableFinder();
        $phpBinaryPath = $phpBinaryFinder->find();

        $this->setSymbolData();
        $this->setCoinData();

        $loop->addPeriodicTimer(3600,function (){
            $this->setSymbolData();
            $this->setCoinData();
        });

        $loop->addPeriodicTimer(env('SECONDS_TIMEOUT'),function () use ($phpBinaryPath){
            Symbol::each(function (Symbol $symbol,int $i) use ($phpBinaryPath){
                (new Process([$phpBinaryPath,base_path('artisan'),'trader:symbol',$symbol->name,$symbol->volume,intdiv($i,250)]))->start();
            });
        });

        $loop->run();
    }

    public function setSymbolData()
    {
        $data = [];

        foreach (config('symbol.exchanges') as $exchange => $exchangeData){
            $data[$exchange] = (new $exchangeData['adapter'](config('symbol.proxies.0')))->symbolData();
        }

        \Storage::put('symbols.json',json_encode($data));
    }

    public function setCoinData()
    {
        $data = [];

        foreach (config('symbol.exchanges') as $exchange => $exchangeData){
            $data[$exchange] = (new $exchangeData['adapter'](config('symbol.proxies.0')))->getAssets();
        }

        \Storage::put('coins.json',json_encode($data));
    }
}
