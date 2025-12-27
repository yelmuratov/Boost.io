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
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('balance', 10, 2)->default(0)->after('role');
            $table->decimal('bonus_balance', 10, 2)->default(0)->after('balance');
            $table->decimal('total_spent', 10, 2)->default(0)->after('bonus_balance');
            $table->boolean('bonus_unlocked')->default(false)->after('total_spent');
            $table->timestamp('bonus_unlocked_at')->nullable()->after('bonus_unlocked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'balance',
                'bonus_balance',
                'total_spent',
                'bonus_unlocked',
                'bonus_unlocked_at'
            ]);
        });
    }
};
