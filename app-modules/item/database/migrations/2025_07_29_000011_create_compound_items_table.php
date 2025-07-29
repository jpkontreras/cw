<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compound_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('child_item_id')->constrained('items')->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->boolean('is_required')->default(true);
            $table->boolean('allow_substitution')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->unique(['parent_item_id', 'child_item_id']);
            $table->index('parent_item_id');
            $table->index('child_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compound_items');
    }
};