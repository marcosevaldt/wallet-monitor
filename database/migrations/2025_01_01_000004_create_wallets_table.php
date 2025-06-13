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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Nome amigável da carteira');
            $table->string('address')->unique()->comment('Endereço da carteira Bitcoin');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('label')->nullable()->comment('Rótulo adicional da carteira');
            $table->bigInteger('balance')->default(0)->comment('Saldo em satoshis');
            $table->integer('total_transactions')->default(0)->comment('Total de transações na blockchain');
            $table->integer('imported_transactions')->default(0)->comment('Transações importadas');
            $table->float('import_progress')->default(0.0)->comment('Progresso da importação');
            $table->timestamp('last_import_at')->nullable()->comment('Última importação');
            $table->integer('send_transactions')->default(0)->comment('Transações enviadas');
            $table->integer('receive_transactions')->default(0)->comment('Transações recebidas');
            $table->timestamps();
            
            // Índices para performance
            $table->index('user_id');
            $table->index('address');
            $table->index('last_import_at');
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
}; 