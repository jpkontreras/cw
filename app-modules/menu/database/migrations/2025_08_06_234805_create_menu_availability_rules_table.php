<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_availability_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->string('rule_type'); // time_based, day_based, date_range, capacity_based
            $table->json('days_of_week')->nullable(); // ['monday', 'tuesday', ...]
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('min_capacity')->nullable();
            $table->integer('max_capacity')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_pattern')->nullable(); // daily, weekly, monthly
            $table->integer('priority')->default(0); // Higher priority rules override lower
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('menu_id');
            $table->index('rule_type');
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_availability_rules');
    }
};