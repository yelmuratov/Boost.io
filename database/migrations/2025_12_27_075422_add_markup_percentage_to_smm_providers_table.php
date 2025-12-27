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
        Schema::table('smm_providers', function (Blueprint $table) {
            $table->decimal('markup_percentage', 5, 2)->default(25.00)->after('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('smm_providers', function (Blueprint $table) {
            $table->dropColumn('markup_percentage');
        });
    }
};
