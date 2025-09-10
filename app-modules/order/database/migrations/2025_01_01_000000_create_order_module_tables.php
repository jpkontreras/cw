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
        // Create orders table with all fields from all migrations
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary(); // UUID as primary key
            $table->uuid('session_id')->nullable(); // Reference to order_sessions table
            $table->string('order_number')->nullable()->unique();
            $table->unsignedBigInteger('user_id')->nullable(); // Nullable for guest orders
            $table->unsignedBigInteger('location_id');
            
            // Currency - ISO 4217 currency code (e.g., 'USD', 'CLP', 'EUR')
            // This captures the location's currency at order creation time
            // and remains fixed for the order's lifetime
            $table->string('currency', 3)
                ->default('CLP')
                ->comment('ISO 4217 currency code from location at order creation');
            
            // Menu references (from 2025_08_06_235000_add_menu_references_to_orders.php)
            $table->unsignedBigInteger('menu_id')->nullable();
            $table->integer('menu_version')->nullable();
            
            $table->string('status', 50)->default('draft');
            $table->string('type', 20)->default('dine_in'); // dine_in, takeout, delivery, catering
            $table->string('priority', 20)->default('normal'); // normal, high
            
            // Customer Information
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            $table->text('delivery_address')->nullable();
            
            // Order Details
            $table->integer('table_number')->nullable();
            $table->unsignedBigInteger('waiter_id')->nullable();
            
            // Financial (using integers for event sourcing - store amounts in cents)
            $table->integer('subtotal')->default(0);
            $table->integer('tax')->default(0);
            $table->integer('tip')->default(0);
            $table->integer('discount')->default(0);
            $table->integer('total')->default(0);
            $table->string('payment_status', 20)->default('pending'); // pending, partial, paid, refunded
            $table->string('payment_method')->nullable(); // cash, card, transfer, other
            
            // Additional Info
            $table->text('notes')->nullable();
            $table->text('special_instructions')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->json('metadata')->nullable();
            
            // View count (from 2025_08_31_024657_add_view_count_to_orders_table.php)
            $table->integer('view_count')->default(0);
            
            // Event Sourcing & Modification Tracking
            $table->integer('modification_count')->default(0);
            $table->timestamp('last_modified_at')->nullable();
            $table->string('last_modified_by')->nullable();
            
            // Timestamps
            $table->timestamp('placed_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('preparing_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('delivering_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('user_id');
            $table->index('location_id');
            $table->index('currency'); // Index for filtering orders by currency
            $table->index('session_id');
            $table->index('waiter_id');
            $table->index('status');
            $table->index('type');
            $table->index('payment_status');
            $table->index(['location_id', 'status']);
            $table->index('placed_at');
            $table->index('order_number');
            $table->index('last_modified_at');
            $table->index('menu_id');
            $table->index(['menu_id', 'menu_version']);
            $table->index(['view_count']);
        });

        // Create order_items table with all fields from all migrations
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('order_id');
            // Foreign key added in 2025_09_01_000000_add_foreign_keys_to_order_module_tables.php
            $table->unsignedBigInteger('item_id');
            
            // Menu references (from 2025_08_06_235000_add_menu_references_to_orders.php)
            $table->unsignedBigInteger('menu_section_id')->nullable();
            $table->unsignedBigInteger('menu_item_id')->nullable();
            
            $table->string('item_name');
            $table->string('base_item_name')->nullable(); // Original item name before modifications
            $table->integer('quantity')->default(1);
            $table->integer('base_price'); // Base price before modifiers (in minor units)
            $table->integer('unit_price'); // Final unit price with modifiers (in minor units)
            $table->integer('modifiers_total')->default(0); // Total price adjustment from modifiers
            $table->integer('total_price'); // Final total price (in minor units)
            $table->string('status', 50)->default('pending');
            $table->string('kitchen_status', 50)->default('pending'); // pending, preparing, ready, served
            $table->string('course', 20)->nullable(); // starter, main, dessert, beverage
            $table->text('notes')->nullable();
            $table->text('special_instructions')->nullable(); // Customer special instructions
            $table->json('modifiers')->nullable(); // Structured modifier data
            $table->json('modifier_history')->nullable(); // Track all modifier changes
            $table->integer('modifier_count')->default(0); // Number of active modifiers
            $table->json('metadata')->nullable();
            $table->timestamp('modified_at')->nullable(); // Last time modifiers changed
            $table->timestamp('prepared_at')->nullable();
            $table->timestamp('served_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('item_id');
            $table->index('status');
            $table->index('kitchen_status');
            $table->index(['order_id', 'status']);
            $table->index(['order_id', 'kitchen_status']);
            $table->index('menu_section_id');
            $table->index('menu_item_id');
        });

        // Create order_status_history table
        Schema::create('order_status_history', function (Blueprint $table) {
            $table->id();
            $table->uuid('order_id');
            // Foreign key added in 2025_09_01_000000_add_foreign_keys_to_order_module_tables.php
            $table->string('from_status', 50);
            $table->string('to_status', 50);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');
            
            // Indexes
            $table->index(['order_id', 'created_at']);
        });

        // Create payment_transactions table
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('order_id');
            // Foreign key added in 2025_09_01_000000_add_foreign_keys_to_order_module_tables.php
            $table->string('method', 50); // cash, credit_card, debit_card, mobile_payment, gift_card, other
            $table->integer('amount'); // Stored in minor units (cents, fils, etc.)
            $table->string('status', 20)->default('pending'); // pending, completed, failed, refunded
            $table->string('reference_number')->nullable();
            $table->json('processor_response')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('order_id');
            $table->index('status');
            $table->index('method');
        });

        // Create order_search_history table (from 2025_08_31_024657_add_view_count_to_orders_table.php)
        Schema::create('order_search_history', function (Blueprint $table) {
            $table->id();
            $table->uuid('search_id');
            $table->uuid('order_id');
            // Foreign key added in 2025_09_01_000000_add_foreign_keys_to_order_module_tables.php
            $table->unsignedBigInteger('user_id')->nullable();
            // Foreign key will be added after users table is created
            $table->timestamp('created_at');
            
            $table->index(['search_id']);
            $table->index(['order_id']);
            $table->index(['created_at']);
        });

        // Create order_promotions table (from 2025_09_05_000001_create_order_promotions_table.php)
        Schema::create('order_promotions', function (Blueprint $table) {
            $table->id();
            $table->uuid('order_id');
            // Foreign key added in 2025_09_01_000000_add_foreign_keys_to_order_module_tables.php
            $table->string('promotion_id'); // Can be string or integer depending on offer module
            $table->integer('discount_amount')->default(0);
            $table->string('type')->nullable(); // percentage, fixed, item, etc
            $table->boolean('auto_applied')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['order_id', 'promotion_id']);
        });

        // Create order_item_modifiers table for detailed modifier tracking
        Schema::create('order_item_modifiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_item_id');
            // Foreign key added in 2025_09_01_000000_add_foreign_keys_to_order_module_tables.php
            $table->string('modifier_id'); // ID from menu system
            $table->string('type', 50); // size, topping, ingredient, preparation, customization
            $table->string('name'); // Display name of modifier
            $table->string('action', 20); // add, remove, replace, modify
            $table->integer('quantity')->default(1);
            $table->integer('unit_price_adjustment'); // Price change per unit (can be negative)
            $table->integer('total_price_adjustment'); // Total price change
            $table->string('group')->nullable(); // Modifier group (e.g., "Size", "Toppings")
            $table->boolean('affects_kitchen')->default(true);
            $table->string('status', 20)->default('active'); // active, cancelled, replaced
            $table->json('metadata')->nullable(); // Additional data
            $table->string('added_by')->nullable(); // Who added this modifier
            $table->timestamp('added_at');
            $table->timestamp('modified_at')->nullable();
            
            $table->index(['order_item_id', 'status']);
            $table->index(['type']);
            $table->index(['modifier_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_item_modifiers');
        Schema::dropIfExists('order_promotions');
        Schema::dropIfExists('order_search_history');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('order_status_history');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};