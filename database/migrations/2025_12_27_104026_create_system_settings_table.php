<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->text('description')->nullable();
            $table->string('type')->default('string'); // string, integer, decimal, boolean, json
            $table->timestamps();
        });

        // Seed initial bonus settings
        DB::table('system_settings')->insert([
            [
                'key' => 'bonus.enabled',
                'value' => 'true',
                'description' => 'Enable or disable the welcome bonus system',
                'type' => 'boolean',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'bonus.registration_amount',
                'value' => '5000.00',
                'description' => 'Amount of welcome bonus awarded on email verification',
                'type' => 'decimal',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'bonus.unlock_threshold',
                'value' => '10000.00',
                'description' => 'Amount user must spend to unlock the bonus',
                'type' => 'decimal',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
