<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modifier_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_group_id')->nullable()
                ->constrained('modifier_groups')
                ->nullOnDelete();
            $table->string('name');
            $table->string('category')->nullable();
            $table->enum('scope', ['global', 'category', 'item'])->default('global');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->enum('selection_type', ['single', 'multiple'])->default('multiple');
            $table->boolean('is_required')->default(false);
            $table->integer('min_selections')->default(0);
            $table->integer('max_selections')->nullable();
            $table->json('display_rules')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index('is_active');
            $table->index('category');
            $table->index('sort_order');
            $table->index('scope');
            $table->index('parent_group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modifier_groups');
    }
};