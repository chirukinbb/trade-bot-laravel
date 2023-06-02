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

        Symbol::each(function (Symbol $symbol,int $i) use ($loop,$phpBinaryPath){
            $loop->addPeriodicTimer(env('SECONDS_TIMEOUT'),function () use ($symbol,$phpBinaryPath,$i){
                (new Process([$phpBinaryPath,base_path('artisan'),'trader:symbol',$symbol->name,$symbol->volume,intdiv($i,250)]))->start();
            });
        });

        $loop->run();
    }
}
