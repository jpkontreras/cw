<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxonomies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->string('type', 50); // item_category, dietary_label, allergen, etc.
            $table->foreignId('parent_id')->nullable()->constrained('taxonomies')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->cascadeOnDelete();
            $table->json('metadata')->nullable(); // For icons, colors, descriptions, etc.
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Unique constraint on slug + type combination
            $table->unique(['slug', 'type']);
            
            // Indexes for performance
            $table->index('type');
            $table->index('slug');
            $table->index(['type', 'is_active']);
            $table->index(['parent_id', 'sort_order']);
            $table->index(['location_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxonomies');
    }
};