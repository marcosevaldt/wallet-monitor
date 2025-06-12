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
            // Verificar se as colunas existem antes de tentar removê-las
            if (Schema::hasColumn('wallets', 'input_transactions')) {
                $table->dropColumn('input_transactions');
            }
            if (Schema::hasColumn('wallets', 'output_transactions')) {
                $table->dropColumn('output_transactions');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            if (!Schema::hasColumn('wallets', 'input_transactions')) {
                $table->integer('input_transactions')->default(0)->after('receive_transactions');
            }
            if (!Schema::hasColumn('wallets', 'output_transactions')) {
                $table->integer('output_transactions')->default(0)->after('input_transactions');
            }
        });
    }
};
