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
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
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
    protected $description = 'php artisan trader:exchanges';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $loop = Loop::get();

        $phpBinaryFinder = new PhpExecutableFinder();
        $phpBinaryPath = $phpBinaryFinder->find();

        Symbol::each(function (Symbol $symbol) use ($loop,$phpBinaryPath){
            $loop->addPeriodicTimer(env('SECONDS_TIMEOUT'),function () use ($symbol,$phpBinaryPath){
                (new Process([$phpBinaryPath,base_path('artisan'),'trader:symbol',$symbol->name,$symbol->volume]))->start();
            });
        });

        $loop->run();
    }
}
