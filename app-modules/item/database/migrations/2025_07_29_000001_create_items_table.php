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
            $table->decimal('base_price', 10, 2);
            $table->decimal('base_cost', 10, 2)->default(0);
            $table->integer('preparation_time')->default(0); // in minutes
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_available')->default(true)->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('track_inventory')->default(false);
            $table->integer('stock_quantity')->default(0);
            $table->integer('low_stock_threshold')->default(10);
            $table->enum('item_type', ['single', 'compound'])->default('single');
            $table->json('allergens')->nullable();
            $table->json('nutritional_info')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamp('available_from')->nullable();
            $table->timestamp('available_until')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            $table->index(['is_active', 'is_available']);
            $table->index('item_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};