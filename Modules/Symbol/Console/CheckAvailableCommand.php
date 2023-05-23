<?php

namespace Modules\Symbol\Console;

use Illuminate\Console\Command;
use Modules\Signal\Entities\Deal;
use Modules\Signal\Entities\Signal;
use Modules\Symbol\Entities\Symbol;
use Modules\Trader\Entities\Trade;

class CheckAvailableCommand extends Command
{
    protected $signature = 'symbol:check';

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
        Symbol::each(function (){});
    }
}
