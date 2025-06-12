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
        // Índices para tabela transactions (apenas os que não existem)
        Schema::table('transactions', function (Blueprint $table) {
            // Índice para valor (filtro de faixa) - não existe
            if (!$this->indexExists('transactions', 'transactions_value_index')) {
                $table->index('value', 'transactions_value_index');
            }
            
            // Índice para data de criação (sortable) - não existe
            if (!$this->indexExists('transactions', 'transactions_created_at_index')) {
                $table->index('created_at', 'transactions_created_at_index');
            }
            
            // Índice composto para tipo e valor (filtros combinados) - não existe
            if (!$this->indexExists('transactions', 'transactions_type_value_index')) {
                $table->index(['type', 'value'], 'transactions_type_value_index');
            }
            
            // Índice composto para carteira, tipo e data - não existe
            if (!$this->indexExists('transactions', 'transactions_wallet_type_time_index')) {
                $table->index(['wallet_id', 'type', 'block_time'], 'transactions_wallet_type_time_index');
            }
        });

        // Índices para tabela bitcoin_price_history (apenas os que não existem)
        Schema::table('bitcoin_price_history', function (Blueprint $table) {
            // Índice para preço de fechamento (filtros de faixa) - não existe
            if (!$this->indexExists('bitcoin_price_history', 'bitcoin_close_index')) {
                $table->index('close', 'bitcoin_close_index');
            }
            
            // Índice para preços OHLC (consultas de estatísticas) - não existe
            if (!$this->indexExists('bitcoin_price_history', 'bitcoin_ohlc_index')) {
                $table->index(['open', 'high', 'low', 'close'], 'bitcoin_ohlc_index');
            }
        });

        // Índices para tabela users (se existir)
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                // Índice para email (login) - verificar se existe
                if (!$this->indexExists('users', 'users_email_index')) {
                    $table->index('email', 'users_email_index');
                }
                
                // Índice para nome (searchable) - verificar se existe
                if (!$this->indexExists('users', 'users_name_index')) {
                    $table->index('name', 'users_name_index');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remover índices da tabela transactions
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndexIfExists('transactions_value_index');
            $table->dropIndexIfExists('transactions_created_at_index');
            $table->dropIndexIfExists('transactions_type_value_index');
            $table->dropIndexIfExists('transactions_wallet_type_time_index');
        });

        // Remover índices da tabela bitcoin_price_history
        Schema::table('bitcoin_price_history', function (Blueprint $table) {
            $table->dropIndexIfExists('bitcoin_close_index');
            $table->dropIndexIfExists('bitcoin_ohlc_index');
        });

        // Remover índices da tabela users (se existir)
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndexIfExists('users_email_index');
                $table->dropIndexIfExists('users_name_index');
            });
        }
    }

    /**
     * Verifica se um índice existe na tabela
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = '{$indexName}'");
        return count($indexes) > 0;
    }
};
