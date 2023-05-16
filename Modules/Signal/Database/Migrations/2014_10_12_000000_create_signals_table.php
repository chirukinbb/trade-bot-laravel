<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('signals', function (Blueprint $table) {
            $table->id();
            $table->string('base_coin');
            $table->string('quote_coin');
            $table->string('buy_prices');
            $table->string('sell_prices');
            $table->string('buy_volumes');
            $table->string('sell_volumes');
            $table->string('sell_exchange');
            $table->string('buy_exchange');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signals');
    }
};
