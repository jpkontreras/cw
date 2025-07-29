<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_variant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->text('instructions');
            $table->integer('prep_time_minutes')->default(0);
            $table->integer('cook_time_minutes')->default(0);
            $table->decimal('yield_quantity', 10, 2)->default(1);
            $table->string('yield_unit')->default('portion');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['item_id', 'item_variant_id']);
            $table->index('item_id');
            $table->index('item_variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};