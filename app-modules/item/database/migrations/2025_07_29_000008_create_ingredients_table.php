<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('unit'); // kg, g, l, ml, unit, etc.
            $table->decimal('cost_per_unit', 10, 2);
            $table->foreignId('supplier_id')->nullable()->index(); // Foreign key to supplier module
            $table->text('storage_requirements')->nullable();
            $table->integer('shelf_life_days')->nullable();
            $table->decimal('current_stock', 10, 3)->default(0);
            $table->decimal('reorder_level', 10, 3)->default(0);
            $table->decimal('reorder_quantity', 10, 3)->default(0);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
            
            $table->index('is_active');
            $table->index('current_stock');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};