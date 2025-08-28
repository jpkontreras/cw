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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Unique location identifier (e.g., "SCL-001")
            $table->string('name'); // Location name
            $table->enum('type', ['restaurant', 'kitchen', 'warehouse', 'central_kitchen'])->default('restaurant');
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            
            // Address information
            $table->string('address');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('country', 2)->default('CL'); // ISO 3166-1 alpha-2
            $table->string('postal_code')->nullable();
            
            // Contact information
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            
            // Location settings
            $table->string('timezone')->default('America/Santiago');
            $table->string('currency', 3)->default('CLP'); // ISO 4217
            
            // Operating information
            $table->json('opening_hours')->nullable(); // {"monday": {"open": "09:00", "close": "22:00"}, ...}
            $table->decimal('delivery_radius', 5, 2)->nullable(); // In kilometers
            $table->json('capabilities')->default('["dine_in", "takeout"]'); // Available services
            
            // Hierarchy and management
            $table->foreignId('parent_location_id')->nullable()->constrained('locations')->onDelete('set null');
            $table->foreignId('manager_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Additional data
            $table->json('metadata')->nullable();
            $table->boolean('is_default')->default(false);
            
            // Timestamps and soft deletes
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('code');
            $table->index('status');
            $table->index('type');
            $table->index(['status', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
