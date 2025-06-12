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
            // Adicionar colunas para dados OHLCV (Open, High, Low, Close, Volume)
            $table->decimal('open', 15, 2)->nullable()->after('price');
            $table->decimal('high', 15, 2)->nullable()->after('open');
            $table->decimal('low', 15, 2)->nullable()->after('high');
            $table->decimal('close', 15, 2)->nullable()->after('low');
            
            // Renomear a coluna 'price' para 'price' (manter compatibilidade)
            // $table->renameColumn('price', 'close'); // Não vamos renomear para manter compatibilidade
            
            // Adicionar coluna para indicar se é um registro diário
            $table->boolean('is_daily')->default(false)->after('currency');
            
            // Adicionar índice para consultas por data
            $table->index(['currency', 'is_daily', 'timestamp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bitcoin_price_history', function (Blueprint $table) {
            $table->dropColumn(['open', 'high', 'low', 'close', 'is_daily']);
            $table->dropIndex(['currency', 'is_daily', 'timestamp']);
        });
    }
};
