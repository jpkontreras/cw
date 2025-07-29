<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->morphs('inventoriable'); // Can be item_id or item_variant_id
            $table->foreignId('location_id')->index(); // Foreign key to location module
            $table->enum('movement_type', [
                'initial', 'purchase', 'sale', 'adjustment', 
                'transfer_in', 'transfer_out', 'waste', 'return', 'production'
            ]);
            $table->decimal('quantity', 10, 3); // Can be negative for reductions
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->decimal('before_quantity', 10, 3);
            $table->decimal('after_quantity', 10, 3);
            $table->string('reference_type')->nullable(); // order, transfer, adjustment, etc.
            $table->string('reference_id')->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('user_id')->nullable()->index(); // Who made the adjustment
            $table->timestamps();
            
            $table->index(['inventoriable_type', 'inventoriable_id']);
            $table->index(['location_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('movement_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};