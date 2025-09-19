<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Complete Order-ES Module Migration
 * Creates all tables needed for pure event-sourced order management
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. ORDERS TABLE - Main order projection from events
        Schema::create('order_es_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('session_id')->nullable()->index();
            $table->string('order_number')->nullable()->unique();
            
            // User & Location
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('location_id')->index();
            $table->string('currency', 3)->default('CLP');
            
            // Menu tracking
            $table->unsignedBigInteger('menu_id')->nullable();
            $table->integer('menu_version')->nullable();
            
            // Order details
            $table->string('status', 50)->default('draft')->index();
            $table->string('type', 20)->default('dine_in')->index();
            $table->string('priority', 20)->default('normal');

            // Order slip tracking (barcode system)
            $table->boolean('slip_printed')->default(false);
            $table->timestamp('printed_at')->nullable();
            $table->string('kitchen_status')->default('pending');
            
            // Customer information
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            $table->text('delivery_address')->nullable();
            
            // Service details
            $table->integer('table_number')->nullable();
            $table->unsignedBigInteger('waiter_id')->nullable();
            
            // Financial (stored as integers - cents)
            $table->integer('subtotal')->default(0);
            $table->integer('tax')->default(0);
            $table->integer('tip')->default(0);
            $table->integer('discount')->default(0);
            $table->integer('total')->default(0);
            
            // Payment
            $table->string('payment_status', 20)->default('pending')->index();
            $table->string('payment_method')->nullable();
            
            // Notes & metadata
            $table->text('notes')->nullable();
            $table->text('special_instructions')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->json('metadata')->nullable();
            
            // Tracking
            $table->integer('view_count')->default(0);
            $table->integer('modification_count')->default(0);
            $table->timestamp('last_modified_at')->nullable();
            $table->string('last_modified_by')->nullable();
            
            // Timeline timestamps
            $table->timestamp('started_at')->nullable()->index(); // When order was started
            $table->timestamp('placed_at')->nullable()->index();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('preparing_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('delivering_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Composite indexes for common queries
            $table->index(['location_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('created_at');
            $table->index('order_number'); // For barcode scanning lookup
        });

        // 2. ORDER ITEMS TABLE - Line items projection
        Schema::create('order_es_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id')->index();
            $table->unsignedBigInteger('item_id')->index();
            $table->unsignedBigInteger('menu_section_id')->nullable();
            $table->unsignedBigInteger('menu_item_id')->nullable();
            
            // Item details
            $table->string('item_name');
            $table->string('base_item_name')->nullable();
            $table->integer('quantity');
            
            // Pricing (integers - cents)
            $table->integer('base_price');
            $table->integer('unit_price');
            $table->integer('modifiers_total')->default(0);
            $table->integer('total_price');
            
            // Status tracking
            $table->string('status', 50)->default('pending')->index();
            $table->string('kitchen_status', 50)->default('pending')->index();
            $table->string('course', 20)->nullable()->default('main');
            
            // Customization
            $table->text('notes')->nullable();
            $table->text('special_instructions')->nullable();
            $table->json('modifiers')->nullable();
            $table->json('modifier_history')->nullable();
            $table->integer('modifier_count')->default(0);
            
            // Metadata
            $table->json('metadata')->nullable();
            
            // Kitchen tracking
            $table->timestamp('modified_at')->nullable();
            $table->timestamp('prepared_at')->nullable();
            $table->timestamp('served_at')->nullable();
            
            $table->timestamps();
            
            // Composite indexes
            $table->index(['order_id', 'status']);
            $table->index(['order_id', 'kitchen_status']);
            
            // Foreign key
            $table->foreign('order_id')
                ->references('id')
                ->on('order_es_orders')
                ->onDelete('cascade');
        });

        // 3. ORDER STATUS HISTORY TABLE - Audit trail
        Schema::create('order_es_status_history', function (Blueprint $table) {
            $table->id();
            $table->uuid('order_id')->index();
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50);
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('created_at');
            
            $table->foreign('order_id')
                ->references('id')
                ->on('order_es_orders')
                ->onDelete('cascade');
        });

        // 4. ORDER SESSIONS TABLE - Session tracking
        Schema::create('order_es_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('staff_id')->nullable()->index();
            $table->unsignedBigInteger('location_id')->index();
            $table->string('status', 50)->default('active')->index();
            $table->string('type', 20)->default('dine_in');
            $table->integer('table_number')->nullable();
            $table->integer('customer_count')->default(1);
            $table->uuid('order_id')->nullable()->index();
            $table->json('metadata')->nullable();
            
            // Timestamps
            $table->timestamp('started_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            
            // Composite index
            $table->index(['location_id', 'status']);
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('order_es_sessions');
        Schema::dropIfExists('order_es_status_history');
        Schema::dropIfExists('order_es_order_items');
        Schema::dropIfExists('order_es_orders');
    }
};