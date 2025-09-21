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
            $table->string('sku')->nullable();
            $table->text('description')->nullable();
            $table->integer('price_adjustment')->default(0); // Can be positive or negative, stored in minor units
            $table->integer('calories')->nullable();
            $table->integer('max_quantity')->default(1);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('track_inventory')->default(false);
            $table->integer('stock_quantity')->nullable();
            $table->json('allergens')->nullable();
            $table->string('image_url')->nullable();
            $table->integer('prep_time_minutes')->nullable();
            $table->time('available_from')->nullable();
            $table->time('available_until')->nullable();
            $table->json('available_days')->nullable(); // ['monday', 'tuesday', etc.]
            $table->json('tags')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['modifier_group_id', 'is_active']);
            $table->index(['modifier_group_id', 'is_default']);
            $table->index('sku');
            $table->index('track_inventory');
        });

        // Create modifier_group_categories table for better organization
        Schema::create('modifier_group_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('slug');
            $table->index('sort_order');
            $table->index('is_active');
        });

        // Create modifier_rules table for complex conditional logic
        Schema::create('modifier_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modifier_group_id')->constrained()->cascadeOnDelete();
            $table->string('rule_type'); // 'requires', 'excludes', 'price_change', etc.
            $table->json('conditions'); // Complex conditions in JSON format
            $table->json('actions'); // Actions to take when conditions are met
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['modifier_group_id', 'is_active']);
        });

        // Create modifier_dependencies table for modifier relationships
        Schema::create('modifier_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modifier_id')->constrained('item_modifiers')->cascadeOnDelete();
            $table->foreignId('depends_on_modifier_id')->constrained('item_modifiers')->cascadeOnDelete();
            $table->enum('dependency_type', ['requires', 'excludes', 'suggests']);
            $table->timestamps();

            $table->unique(['modifier_id', 'depends_on_modifier_id', 'dependency_type'], 'unique_modifier_dependency');
            $table->index('depends_on_modifier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modifier_dependencies');
        Schema::dropIfExists('modifier_rules');
        Schema::dropIfExists('modifier_group_categories');
        Schema::dropIfExists('item_modifiers');
    }
};