<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type', 50)->default('string');
            $table->string('category', 50);
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('group', 100)->nullable();
            $table->json('options')->nullable();
            $table->json('validation')->nullable();
            $table->text('default_value')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_public')->default(false);
            $table->boolean('is_encrypted')->default(false);
            $table->integer('sort_order')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('category');
            $table->index('group');
            $table->index(['category', 'group']);
            $table->index('is_required');
            $table->index('is_public');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};