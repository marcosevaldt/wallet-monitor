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
            $table->integer('total_transactions')->default(0);
            $table->integer('imported_transactions')->default(0);
            $table->float('import_progress')->default(0.0);
            $table->timestamp('last_import_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn(['total_transactions', 'imported_transactions', 'import_progress', 'last_import_at']);
        });
    }
}; 