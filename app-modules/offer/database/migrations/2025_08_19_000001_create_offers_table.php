<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('description')->nullable();
            $table->string('type', 50); // percentage, fixed, buy_x_get_y, combo, etc.
            $table->decimal('value', 10, 2);
            $table->decimal('max_discount', 10, 2)->nullable();
            $table->string('code', 50)->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_apply')->default(false);
            $table->boolean('is_stackable')->default(false);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('recurring_schedule')->nullable();
            $table->json('valid_days')->nullable();
            $table->time('valid_time_start')->nullable();
            $table->time('valid_time_end')->nullable();
            $table->decimal('minimum_amount', 10, 2)->nullable();
            $table->integer('minimum_quantity')->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('usage_per_customer')->nullable();
            $table->integer('usage_count')->default(0);
            $table->integer('priority')->nullable();
            $table->json('location_ids')->nullable();
            $table->json('target_item_ids')->nullable();
            $table->json('target_category_ids')->nullable();
            $table->json('excluded_item_ids')->nullable();
            $table->json('customer_segments')->nullable();
            $table->json('conditions')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index('is_active');
            $table->index('code');
            $table->index('type');
            $table->index('starts_at');
            $table->index('ends_at');
            $table->index('priority');
            $table->index(['is_active', 'starts_at', 'ends_at']);
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};