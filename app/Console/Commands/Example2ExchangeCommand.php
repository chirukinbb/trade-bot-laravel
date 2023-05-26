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
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
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
        $loop = Loop::get();

        $phpBinaryFinder = new PhpExecutableFinder();
        $phpBinaryPath = $phpBinaryFinder->find();

        $loop->addPeriodicTimer(2,function ()use ($phpBinaryPath){
            $proc = new Process([$phpBinaryPath,'trader.php','ddd',1]);

            $proc->start();
        });

        $loop->addPeriodicTimer(1,function ()use ($phpBinaryPath){
            $proc = new Process([$phpBinaryPath,'trader.php','ggg',3]);

            $proc->start();
        });


        $loop->run();
    }
}
