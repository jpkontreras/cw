<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_modifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modifier_group_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('price_adjustment')->default(0); // Can be positive or negative, stored in minor units
            $table->integer('max_quantity')->default(1);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['modifier_group_id', 'is_active']);
            $table->index(['modifier_group_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_modifiers');
    }
};