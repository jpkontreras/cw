<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
	public function up(): void
	{
		// Create items table
		Schema::create('items', function(Blueprint $table) {
			$table->id();
			$table->string('name');
			$table->string('sku', 100)->unique();
			$table->text('description')->nullable();
			$table->decimal('base_price', 10, 2);
			$table->string('unit', 50)->nullable(); // 'piece', 'kg', 'liter', etc.
			$table->unsignedBigInteger('category_id');
			$table->enum('type', ['simple', 'variant', 'compound'])->default('simple');
			$table->enum('status', ['active', 'inactive', 'discontinued'])->default('active');
			$table->boolean('is_available')->default(true);
			$table->boolean('track_inventory')->default(false);
			$table->integer('current_stock')->nullable();
			$table->integer('low_stock_threshold')->nullable();
			$table->json('images')->nullable();
			$table->json('metadata')->nullable();
			$table->timestamps();
			$table->softDeletes();
			
			// Indexes
			$table->index('category_id');
			$table->index('status');
			$table->index('type');
			$table->index(['status', 'is_available']);
			$table->fullText(['name', 'description']);
		});

		// Create item_variants table
		Schema::create('item_variants', function(Blueprint $table) {
			$table->id();
			$table->foreignId('item_id')->constrained('items')->onDelete('cascade');
			$table->string('name');
			$table->string('sku', 100)->unique();
			$table->string('attribute_type', 50)->nullable(); // 'size', 'color', 'style'
			$table->string('attribute_value')->nullable();
			$table->decimal('price_adjustment', 10, 2)->default(0);
			$table->decimal('weight', 10, 3)->nullable();
			$table->boolean('is_available')->default(true);
			$table->boolean('is_default')->default(false);
			$table->integer('current_stock')->nullable();
			$table->json('images')->nullable();
			$table->json('metadata')->nullable();
			$table->timestamps();
			
			// Indexes
			$table->index(['item_id', 'is_available']);
			$table->index('attribute_type');
		});

		// Create item_modifier_groups table
		Schema::create('item_modifier_groups', function(Blueprint $table) {
			$table->id();
			$table->string('name');
			$table->text('description')->nullable();
			$table->enum('type', ['single', 'multiple'])->default('single');
			$table->boolean('is_required')->default(false);
			$table->integer('min_selections')->nullable();
			$table->integer('max_selections')->nullable();
			$table->integer('sort_order')->default(0);
			$table->timestamps();
			
			// Indexes
			$table->index('type');
			$table->index('sort_order');
		});

		// Create item_modifiers table
		Schema::create('item_modifiers', function(Blueprint $table) {
			$table->id();
			$table->foreignId('group_id')->constrained('item_modifier_groups')->onDelete('cascade');
			$table->string('name');
			$table->text('description')->nullable();
			$table->decimal('price', 10, 2)->default(0);
			$table->boolean('is_available')->default(true);
			$table->boolean('is_default')->default(false);
			$table->integer('sort_order')->default(0);
			$table->integer('max_quantity')->nullable();
			$table->json('metadata')->nullable();
			$table->timestamps();
			
			// Indexes
			$table->index(['group_id', 'is_available']);
			$table->index('sort_order');
		});

		// Create item_modifier_group_items pivot table
		Schema::create('item_modifier_group_items', function(Blueprint $table) {
			$table->id();
			$table->foreignId('item_id')->constrained('items')->onDelete('cascade');
			$table->foreignId('modifier_group_id')->constrained('item_modifier_groups')->onDelete('cascade');
			$table->integer('sort_order')->default(0);
			$table->timestamps();
			
			// Unique constraint
			$table->unique(['item_id', 'modifier_group_id']);
			
			// Indexes
			$table->index('item_id');
			$table->index('modifier_group_id');
		});

		// Create item_pricing table for location-specific pricing
		Schema::create('item_pricing', function(Blueprint $table) {
			$table->id();
			$table->foreignId('item_id')->constrained('items')->onDelete('cascade');
			$table->unsignedBigInteger('location_id');
			$table->decimal('price', 10, 2);
			$table->decimal('cost_price', 10, 2)->nullable();
			$table->decimal('sale_price', 10, 2)->nullable();
			$table->boolean('is_on_sale')->default(false);
			$table->timestamp('sale_price_starts_at')->nullable();
			$table->timestamp('sale_price_ends_at')->nullable();
			$table->json('metadata')->nullable();
			$table->timestamps();
			
			// Unique constraint
			$table->unique(['item_id', 'location_id']);
			
			// Indexes
			$table->index('location_id');
			$table->index(['item_id', 'location_id']);
			$table->index('is_on_sale');
		});

		// Create item_ingredients table for compound items
		Schema::create('item_ingredients', function(Blueprint $table) {
			$table->id();
			$table->foreignId('item_id')->constrained('items')->onDelete('cascade');
			$table->foreignId('ingredient_id')->constrained('items')->onDelete('restrict');
			$table->decimal('quantity', 10, 3);
			$table->string('unit', 50)->nullable();
			$table->timestamps();
			
			// Unique constraint
			$table->unique(['item_id', 'ingredient_id']);
			
			// Indexes
			$table->index('item_id');
			$table->index('ingredient_id');
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('item_ingredients');
		Schema::dropIfExists('item_pricing');
		Schema::dropIfExists('item_modifier_group_items');
		Schema::dropIfExists('item_modifiers');
		Schema::dropIfExists('item_modifier_groups');
		Schema::dropIfExists('item_variants');
		Schema::dropIfExists('items');
	}
};
