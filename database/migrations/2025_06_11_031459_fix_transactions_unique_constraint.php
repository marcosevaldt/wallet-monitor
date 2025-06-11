<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Verificar se a chave única já existe antes de criar
            $indexes = DB::select("SHOW INDEX FROM transactions WHERE Key_name = 'transactions_unique_composite'");
            
            if (empty($indexes)) {
                // Adicionar chave única composta para evitar duplicatas específicas
                $table->unique(['wallet_id', 'tx_hash', 'type', 'address'], 'transactions_unique_composite');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Verificar se a chave única existe antes de tentar removê-la
            $indexes = DB::select("SHOW INDEX FROM transactions WHERE Key_name = 'transactions_unique_composite'");
            
            if (!empty($indexes)) {
                // Remover a chave única composta
                $table->dropUnique('transactions_unique_composite');
            }
        });
    }
};
