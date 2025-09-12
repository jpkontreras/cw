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
        // Order sessions table - tracks active and abandoned sessions
        Schema::create('order_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('user_id')->nullable();
            
            // Location and business IDs - foreign keys added conditionally below
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('business_id')->nullable();
            
            $table->string('status', 50)->default('initiated'); // initiated, cart_building, details_collecting, abandoned, converted
            $table->string('serving_type', 20)->nullable(); // dine_in, takeout, delivery
            $table->json('device_info')->nullable();
            $table->string('referrer')->nullable();
            $table->integer('cart_items_count')->default(0);
            $table->decimal('cart_value', 10, 2)->default(0);
            $table->boolean('customer_info_complete')->default(false);
            $table->string('payment_method', 20)->nullable();
            $table->integer('session_duration')->nullable(); // in seconds
            $table->string('abandonment_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->json('cart_items')->nullable(); // Event-sourced cart state projection
            $table->string('table_number', 20)->nullable(); // For dine-in orders
            $table->string('delivery_address')->nullable(); // For delivery orders
            $table->string('order_id')->nullable(); // Reference to created order UUID
            $table->timestamp('started_at');
            $table->timestamp('last_activity_at');
            $table->timestamp('abandoned_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamp('draft_saved_at')->nullable(); // When draft was last saved
            $table->timestamps();
            
            // Indexes
            $table->index('uuid');
            $table->index('user_id');
            $table->index('location_id');
            $table->index('business_id');
            $table->index('status');
            $table->index('started_at');
            $table->index('last_activity_at');
            $table->index(['status', 'last_activity_at']); // For finding stale sessions
            
            // Foreign keys are added in a separate migration (2025_09_01_000000_add_foreign_keys_to_order_sessions.php)
            // to ensure businesses and locations tables exist first
        });

        // Order drafts - saved cart states
        Schema::create('order_drafts', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique(); // UUID of the session
            $table->json('cart_items');
            $table->json('customer_info')->nullable();
            $table->string('serving_type', 20)->nullable();
            $table->string('payment_method', 20)->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->boolean('auto_saved')->default(false);
            $table->timestamp('saved_at');
            $table->timestamps();
            
            // Indexes
            $table->index('session_id');
            $table->index('saved_at');
        });

        // Order analytics - aggregated metrics
        Schema::create('order_analytics', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->nullable(); // UUID of the session
            $table->string('event_name', 50);
            $table->json('event_data');
            $table->timestamp('created_at');
            
            // Indexes
            $table->index('session_id');
            $table->index('event_name');
            $table->index('created_at');
            $table->index(['event_name', 'created_at']);
        });

        // Search analytics - track search behavior
        Schema::create('search_analytics', function (Blueprint $table) {
            $table->id();
            $table->string('search_term');
            $table->integer('results_count')->default(0);
            $table->timestamp('searched_at');
            $table->timestamps();
            
            // Indexes
            $table->index('search_term');
            $table->index('searched_at');
            $table->index(['search_term', 'searched_at']);
        });

        // Category analytics - track category popularity
        Schema::create('category_analytics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->integer('view_count')->default(0);
            $table->integer('item_selections')->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);
            $table->timestamps();
            
            // Indexes
            $table->unique('category_id');
            $table->index('view_count');
            $table->index('conversion_rate');
        });

        // Item analytics - track item popularity
        Schema::create('item_analytics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->integer('view_count')->default(0);
            $table->integer('cart_additions')->default(0);
            $table->integer('cart_removals')->default(0);
            $table->integer('final_purchases')->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);
            $table->timestamps();
            
            // Indexes
            $table->unique('item_id');
            $table->index('view_count');
            $table->index('cart_additions');
            $table->index('conversion_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_analytics');
        Schema::dropIfExists('category_analytics');
        Schema::dropIfExists('search_analytics');
        Schema::dropIfExists('order_analytics');
        Schema::dropIfExists('order_drafts');
        Schema::dropIfExists('order_sessions');
    }
};