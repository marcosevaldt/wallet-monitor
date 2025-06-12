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
        Schema::table('bitcoin_price_history', function (Blueprint $table) {
            // Remover a constraint única do timestamp
            $table->dropUnique(['timestamp']);
            
            // Adicionar índice composto para evitar duplicatas por data e moeda
            $table->unique(['timestamp', 'currency', 'is_daily'], 'bitcoin_price_history_unique_daily');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bitcoin_price_history', function (Blueprint $table) {
            // Remover o índice composto
            $table->dropUnique('bitcoin_price_history_unique_daily');
            
            // Restaurar a constraint única do timestamp
            $table->unique(['timestamp']);
        });
    }
};
