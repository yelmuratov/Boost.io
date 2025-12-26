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
        Schema::create('smm_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('api_url');
            $table->string('api_key');
            $table->boolean('is_active')->default(true);
            $table->string('verification_status')->default('pending'); // pending, verified, failed
            $table->integer('priority')->default(0); // Higher priority used first
            $table->decimal('balance', 10, 2)->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->json('metadata')->nullable(); // Additional config
            $table->timestamps();
        });

        Schema::create('smm_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('smm_providers')->onDelete('cascade');
            $table->string('service_id'); // Provider's service ID
            $table->string('name');
            $table->string('type'); // followers, likes, views, etc.
            $table->string('category')->nullable();
            $table->decimal('rate', 10, 4); // Price per 1000
            $table->integer('min')->nullable();
            $table->integer('max')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Store full service data
            $table->timestamps();

            $table->unique(['provider_id', 'service_id']);
        });

        Schema::create('smm_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('provider_id')->constrained('smm_providers');
            $table->foreignId('service_id')->constrained('smm_services');
            $table->string('order_id'); // Provider's order ID
            $table->string('link');
            $table->integer('quantity')->nullable();
            $table->decimal('charge', 10, 2)->nullable();
            $table->decimal('cost', 10, 2)->nullable(); // What we paid
            $table->integer('start_count')->nullable();
            $table->integer('remains')->nullable();
            $table->string('status')->default('pending');
            $table->json('order_data')->nullable(); // Store order parameters
            $table->json('response_data')->nullable();
            $table->timestamps();

            $table->index(['provider_id', 'order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('smm_orders');
        Schema::dropIfExists('smm_services');
        Schema::dropIfExists('smm_providers');
    }
};
