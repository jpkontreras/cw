<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_location_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_variant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->index(); // Foreign key to location module
            $table->decimal('quantity', 10, 3)->default(0);
            $table->decimal('reserved_quantity', 10, 3)->default(0); // For pending orders
            $table->decimal('available_quantity', 10, 3)->virtualAs('quantity - reserved_quantity');
            $table->decimal('reorder_point', 10, 3)->default(0);
            $table->decimal('reorder_quantity', 10, 3)->default(0);
            $table->timestamps();
            
            $table->unique(['item_id', 'item_variant_id', 'location_id'], 'item_variant_location_unique');
            $table->index(['location_id', 'quantity']);
            $table->index(['location_id', 'available_quantity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_location_stock');
    }
};