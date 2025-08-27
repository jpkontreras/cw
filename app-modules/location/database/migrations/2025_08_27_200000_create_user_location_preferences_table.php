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
        // Create the module-specific user location preferences table
        Schema::create('user_location_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->foreignId('current_location_id')->nullable()->constrained('locations')->onDelete('set null');
            $table->foreignId('default_location_id')->nullable()->constrained('locations')->onDelete('set null');
            $table->timestamps();

            $table->index('current_location_id');
            $table->index('default_location_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_location_preferences');
    }
};