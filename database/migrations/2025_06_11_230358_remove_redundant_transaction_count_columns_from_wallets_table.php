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
        Schema::table('wallets', function (Blueprint $table) {
            // Remover campos redundantes - input_transactions e output_transactions
            // são redundantes com send_transactions e receive_transactions
            $table->dropColumn(['input_transactions', 'output_transactions']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->integer('input_transactions')->default(0)->after('receive_transactions');
            $table->integer('output_transactions')->default(0)->after('input_transactions');
        });
    }
};
