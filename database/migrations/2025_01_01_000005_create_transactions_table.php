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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->string('tx_hash')->comment('Hash da transação');
            $table->bigInteger('block_height')->nullable()->comment('Altura do bloco');
            $table->bigInteger('tx_index')->nullable()->comment('Índice da transação');
            $table->bigInteger('value')->default(0)->comment('Valor em satoshis');
            $table->enum('type', ['input', 'output', 'send', 'receive'])->comment('Tipo da transação');
            $table->string('address')->nullable()->comment('Endereço relacionado');
            $table->json('raw_data')->nullable()->comment('Dados brutos da API');
            $table->timestamp('block_time')->nullable()->comment('Tempo do bloco');
            $table->timestamps();
            
            // Chave única composta para evitar duplicatas
            $table->unique(['wallet_id', 'tx_hash', 'type', 'address'], 'transactions_unique_composite');
            
            // Índices para performance
            $table->index(['wallet_id', 'tx_hash']);
            $table->index(['wallet_id', 'block_time'], 'transactions_wallet_time_index');
            $table->index('block_time', 'transactions_block_time_index');
            $table->index('type', 'transactions_type_index');
            $table->index('address', 'transactions_address_index');
            $table->index('value', 'transactions_value_index');
            $table->index('created_at', 'transactions_created_at_index');
            $table->index(['type', 'value'], 'transactions_type_value_index');
            $table->index(['wallet_id', 'type', 'block_time'], 'transactions_wallet_type_time_index');
            $table->index(['block_height', 'block_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
}; 