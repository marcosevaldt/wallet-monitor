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
        Schema::table('transactions', function (Blueprint $table) {
            // Índice composto otimizado para consultas por wallet_id e ordenação por block_time
            $table->index(['wallet_id', 'block_time'], 'transactions_wallet_time_index');
            
            // Índice para block_time para melhorar ordenação
            $table->index('block_time', 'transactions_block_time_index');
            
            // Índice para type para filtros
            $table->index('type', 'transactions_type_index');
            
            // Índice para address para buscas
            $table->index('address', 'transactions_address_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_wallet_time_index');
            $table->dropIndex('transactions_block_time_index');
            $table->dropIndex('transactions_type_index');
            $table->dropIndex('transactions_address_index');
        });
    }
};
