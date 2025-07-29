<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->index(); // Foreign key to taxonomy module
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            
            $table->unique(['item_id', 'category_id']);
            $table->index(['item_id', 'is_primary']);
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_categories');
    }
};