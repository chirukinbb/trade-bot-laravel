<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DBCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db';

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
        Schema::table('signals', function (Blueprint $table) {
            $table->addColumn('string','sell_exchange',['length'=>100]);
            $table->addColumn('string','buy_exchange',['length'=>100]);
        });
    }
}
