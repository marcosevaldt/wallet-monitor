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
        Schema::create('import_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->string('job_type')->comment('Tipo do job: import ou update');
            $table->string('status')->comment('Status: pending, running, completed, failed');
            $table->integer('progress')->default(0)->comment('Progresso em porcentagem');
            $table->integer('total_transactions')->default(0)->comment('Total de transações');
            $table->integer('imported_transactions')->default(0)->comment('Transações importadas');
            $table->integer('send_transactions')->default(0)->comment('Transações enviadas');
            $table->integer('receive_transactions')->default(0)->comment('Transações recebidas');
            $table->text('error_message')->nullable()->comment('Mensagem de erro');
            $table->timestamp('started_at')->nullable()->comment('Início da execução');
            $table->timestamp('completed_at')->nullable()->comment('Fim da execução');
            $table->timestamps();
            
            // Índices para performance
            $table->index(['wallet_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['wallet_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_jobs');
    }
}; 