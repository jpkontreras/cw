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
			
			// Financial
			$table->decimal('subtotal', 10, 2)->default(0);
			$table->decimal('tax_amount', 10, 2)->default(0);
			$table->decimal('tip_amount', 10, 2)->default(0);
			$table->decimal('discount_amount', 10, 2)->default(0);
			$table->decimal('total_amount', 10, 2)->default(0);
			$table->string('payment_status', 20)->default('pending'); // pending, partial, paid, refunded
			
			// Additional Info
			$table->text('notes')->nullable();
			$table->text('special_instructions')->nullable();
			$table->text('cancel_reason')->nullable();
			$table->json('metadata')->nullable();
			
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
			$table->index('waiter_id');
			$table->index('status');
			$table->index('type');
			$table->index('payment_status');
			$table->index(['location_id', 'status']);
			$table->index('placed_at');
			$table->index('order_number');
		});

		// Create order_items table
		Schema::create('order_items', function(Blueprint $table) {
			$table->id();
			$table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
			$table->unsignedBigInteger('item_id');
			$table->string('item_name');
			$table->integer('quantity')->default(1);
			$table->decimal('unit_price', 10, 2);
			$table->decimal('total_price', 10, 2);
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
			$table->decimal('amount', 10, 2);
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
