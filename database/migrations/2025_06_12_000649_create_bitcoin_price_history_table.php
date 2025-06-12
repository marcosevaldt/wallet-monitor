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
            $table->timestamp('timestamp')->unique();
            $table->decimal('price', 15, 2);
            $table->decimal('volume', 20, 2)->nullable();
            $table->decimal('market_cap', 20, 2)->nullable();
            $table->string('currency', 10)->default('usd');
            $table->timestamps();
            
            // Índices para performance
            $table->index('timestamp');
            $table->index('currency');
            $table->index(['currency', 'timestamp']);
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
