<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create staff role metadata table for additional role information
        Schema::create('staff_role_metadata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->unique()->constrained('roles')->cascadeOnDelete();
            $table->integer('hierarchy_level')->default(10);
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->json('permissions_summary')->nullable(); // Optional: cache common permissions
            $table->json('metadata')->nullable(); // Additional metadata
            $table->timestamps();
            
            $table->index('hierarchy_level');
            $table->index('is_system');
        });
        
        // Create staff-location-role assignments
        Schema::create('staff_location_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('assigned_at');
            $table->foreignId('assigned_by')->nullable()->constrained('staff_members')->nullOnDelete();
            $table->timestamp('expires_at')->nullable();
            
            $table->unique(['staff_member_id', 'role_id', 'location_id'], 'staff_role_location_unique');
            $table->index('location_id');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_location_roles');
        Schema::dropIfExists('staff_role_metadata');
    }
};