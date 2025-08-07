<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_item_modifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')->constrained('menu_items')->cascadeOnDelete();
            $table->unsignedBigInteger('modifier_group_id'); // References item module
            $table->unsignedBigInteger('modifier_id')->nullable(); // Specific modifier, null for entire group
            $table->boolean('is_required')->default(false);
            $table->boolean('is_available')->default(true);
            $table->decimal('price_override', 8, 2)->nullable();
            $table->integer('min_selections')->nullable();
            $table->integer('max_selections')->nullable();
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('menu_item_id');
            $table->index('modifier_group_id');
            $table->index('modifier_id');
            $table->index('is_available');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_item_modifiers');
    }
};