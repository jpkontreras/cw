<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->unsignedBigInteger('location_id'); // References location module
            $table->boolean('is_active')->default(true);
            $table->boolean('is_primary')->default(false); // Primary menu for this location
            $table->dateTime('activated_at')->nullable();
            $table->dateTime('deactivated_at')->nullable();
            $table->json('overrides')->nullable(); // Location-specific overrides
            $table->timestamps();
            
            $table->unique(['menu_id', 'location_id']);
            $table->index('location_id');
            $table->index('is_active');
            $table->index('is_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_locations');
    }
};