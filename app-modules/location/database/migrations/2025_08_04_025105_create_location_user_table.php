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
        Schema::create('location_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('role', ['manager', 'staff', 'viewer'])->default('staff');
            $table->boolean('is_primary')->default(false); // Primary location for the user
            $table->timestamps();
            
            // Ensure a user can only be assigned once to a location
            $table->unique(['location_id', 'user_id']);
            
            // Indexes for better query performance
            $table->index('user_id');
            $table->index('location_id');
            $table->index(['user_id', 'is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_user');
    }
};