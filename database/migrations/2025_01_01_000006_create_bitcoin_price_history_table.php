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
        Schema::create('bitcoin_price_history', function (Blueprint $table) {
            $table->id();
            $table->timestamp('timestamp')->comment('Data/hora do preço');
            $table->decimal('price', 15, 2)->comment('Preço em USD');
            $table->decimal('open', 15, 2)->nullable()->comment('Preço de abertura');
            $table->decimal('high', 15, 2)->nullable()->comment('Preço máximo');
            $table->decimal('low', 15, 2)->nullable()->comment('Preço mínimo');
            $table->decimal('close', 15, 2)->nullable()->comment('Preço de fechamento');
            $table->string('currency', 10)->default('usd')->comment('Moeda de cotação');
            $table->boolean('is_daily')->default(false)->comment('Indica se é registro diário');
            $table->timestamps();
            
            // Índice composto para evitar duplicatas por data e moeda
            $table->unique(['timestamp', 'currency', 'is_daily'], 'bitcoin_price_history_unique_daily');
            
            // Índices para performance
            $table->index('timestamp');
            $table->index('currency');
            $table->index(['currency', 'is_daily', 'timestamp']);
            $table->index('close', 'bitcoin_close_index');
            $table->index(['open', 'high', 'low', 'close'], 'bitcoin_ohlc_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bitcoin_price_history');
    }
}; 