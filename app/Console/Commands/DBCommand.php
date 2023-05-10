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
        Schema::table('symbols', function (Blueprint $table) {
            $table->addColumn('integer','volume');
        });
    }
}
