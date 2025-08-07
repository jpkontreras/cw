<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->foreignId('menu_section_id')->constrained('menu_sections')->cascadeOnDelete();
            $table->unsignedBigInteger('item_id'); // References item module
            $table->string('display_name')->nullable(); // Override item name for this menu
            $table->text('display_description')->nullable(); // Override item description
            $table->decimal('price_override', 10, 2)->nullable(); // Menu-specific pricing
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_recommended')->default(false);
            $table->boolean('is_new')->default(false);
            $table->boolean('is_seasonal')->default(false);
            $table->integer('sort_order')->default(0);
            $table->integer('preparation_time_override')->nullable(); // Minutes
            $table->json('available_modifiers')->nullable(); // Subset of item modifiers
            $table->json('dietary_labels')->nullable(); // Vegan, Gluten-free, etc.
            $table->json('allergen_info')->nullable();
            $table->integer('calorie_count')->nullable();
            $table->json('nutritional_info')->nullable();
            $table->string('image_url')->nullable(); // Menu-specific image
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->unique(['menu_id', 'item_id']);
            $table->index('menu_section_id');
            $table->index('item_id');
            $table->index('is_active');
            $table->index('is_featured');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};