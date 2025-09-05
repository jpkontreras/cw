<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
	public function up(): void
	{
		// Create orders table
		Schema::create('orders', function(Blueprint $table) {
			$table->id();
			$table->uuid('uuid')->unique(); // UUID for event sourcing
			$table->string('order_number')->unique();
			$table->unsignedBigInteger('user_id')->nullable(); // Nullable for guest orders
			$table->unsignedBigInteger('location_id');
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
			$table->index('uuid');
			$table->index('user_id');
			$table->index('location_id');
			$table->index('waiter_id');
			$table->index('status');
			$table->index('type');
			$table->index('payment_status');
			$table->index(['location_id', 'status']);
			$table->index('placed_at');
			$table->index('order_number');
			$table->index('last_modified_at');
		});

		// Create order_items table
		Schema::create('order_items', function(Blueprint $table) {
			$table->id();
			$table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
			$table->unsignedBigInteger('item_id');
			$table->string('item_name');
			$table->integer('quantity')->default(1);
			$table->integer('unit_price'); // Stored in minor units (cents, fils, etc.)
			$table->integer('total_price'); // Stored in minor units (cents, fils, etc.)
			$table->string('status', 50)->default('pending');
			$table->string('kitchen_status', 50)->default('pending'); // pending, preparing, ready, served
			$table->string('course', 20)->nullable(); // starter, main, dessert, beverage
			$table->text('notes')->nullable();
			$table->json('modifiers')->nullable();
			$table->json('metadata')->nullable();
			$table->timestamp('prepared_at')->nullable();
			$table->timestamp('served_at')->nullable();
			$table->timestamps();
			
			// Indexes
			$table->index('item_id');
			$table->index('status');
			$table->index('kitchen_status');
			$table->index(['order_id', 'status']);
			$table->index(['order_id', 'kitchen_status']);
		});

		// Create order_status_history table
		Schema::create('order_status_history', function(Blueprint $table) {
			$table->id();
			$table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
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
		Schema::create('payment_transactions', function(Blueprint $table) {
			$table->id();
			$table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
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
	}

	public function down(): void
	{
		Schema::dropIfExists('payment_transactions');
		Schema::dropIfExists('order_status_history');
		Schema::dropIfExists('order_items');
		Schema::dropIfExists('orders');
	}
};
