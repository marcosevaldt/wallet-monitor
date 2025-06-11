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
            $table->integer('send_transactions')->default(0)->after('imported_transactions');
            $table->integer('receive_transactions')->default(0)->after('send_transactions');
            $table->integer('input_transactions')->default(0)->after('receive_transactions');
            $table->integer('output_transactions')->default(0)->after('input_transactions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn(['send_transactions', 'receive_transactions', 'input_transactions', 'output_transactions']);
        });
    }
}; 