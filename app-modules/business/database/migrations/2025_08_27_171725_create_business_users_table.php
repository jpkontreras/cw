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
        Schema::create('business_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Role within the business
            $table->string('role')->default('member'); // owner, admin, manager, member
            $table->json('permissions')->nullable(); // Business-specific permissions
            
            // Status
            $table->enum('status', ['active', 'inactive', 'pending'])->default('active');
            $table->boolean('is_owner')->default(false);
            
            // Invitation tracking
            $table->string('invitation_token')->nullable();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->foreignId('invited_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Access control
            $table->timestamp('last_accessed_at')->nullable();
            $table->json('preferences')->nullable(); // User preferences for this business
            
            $table->timestamps();
            
            // Ensure a user can only be in a business once
            $table->unique(['business_id', 'user_id']);
            
            // Indexes
            $table->index('role');
            $table->index('status');
            $table->index('invitation_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_users');
    }
};