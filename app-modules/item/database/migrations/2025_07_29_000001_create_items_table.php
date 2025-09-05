<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique()->index();
            $table->text('description')->nullable();
            $table->string('sku')->nullable()->unique()->index();
            $table->string('barcode')->nullable()->unique()->index();
            $table->integer('base_price')->nullable(); // Stored in minor units (cents, fils, etc.)
            $table->integer('sale_price')->nullable(); // Promotional price in minor units
            $table->integer('cost')->default(0)->nullable(); // Stored in minor units (cents, fils, etc.)
            $table->integer('preparation_time')->default(0)->nullable(); // in minutes
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_available')->default(true)->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('track_inventory')->default(false);
            $table->integer('stock_quantity')->default(0)->nullable();
            $table->integer('low_stock_threshold')->default(10)->nullable();
            $table->enum('type', ['product', 'service', 'combo'])->default('product');
            $table->json('allergens')->nullable();
            $table->json('nutritional_info')->nullable();
            $table->integer('sort_order')->default(0)->nullable();
            $table->timestamp('available_from')->nullable();
            $table->timestamp('available_until')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            $table->index(['is_active', 'is_available']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};