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
        Schema::create('location_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->onDelete('cascade');
            $table->string('key'); // Setting key
            $table->text('value')->nullable(); // Setting value (stored as JSON for complex values)
            $table->string('type')->default('string'); // Type hint: string, boolean, integer, json, array
            $table->text('description')->nullable(); // Description of the setting
            $table->boolean('is_encrypted')->default(false); // Whether the value is encrypted
            $table->timestamps();
            
            // Ensure unique settings per location
            $table->unique(['location_id', 'key']);
            
            // Indexes
            $table->index('location_id');
            $table->index('key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_settings');
    }
};