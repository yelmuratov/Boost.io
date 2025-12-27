<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('smm_services', function (Blueprint $table) {
            // Add cost field (what we pay the provider)
            $table->decimal('cost', 10, 4)->default(0)->after('category');

            // Rename 'rate' to be clear it's customer-facing
            // Note: 'rate' already exists, we just add 'cost' alongside it
            // rate = customer price, cost = provider price
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('smm_services', function (Blueprint $table) {
            $table->dropColumn('cost');
        });
    }
};
