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
        Schema::table('bitcoin_price_history', function (Blueprint $table) {
            $table->dropColumn(['volume', 'market_cap']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bitcoin_price_history', function (Blueprint $table) {
            $table->decimal('volume', 20, 2)->nullable()->after('close');
            $table->decimal('market_cap', 20, 2)->nullable()->after('volume');
        });
    }
};
