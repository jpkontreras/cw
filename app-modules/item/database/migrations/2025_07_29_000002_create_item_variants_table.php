<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('sku')->nullable()->unique()->index();
            $table->decimal('price_adjustment', 10, 2)->default(0); // Can be positive or negative
            $table->decimal('size_multiplier', 5, 2)->default(1); // For portion sizes
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('stock_quantity')->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['item_id', 'is_active']);
            $table->index(['item_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_variants');
    }
};