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
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('legal_name')->nullable();
            $table->string('tax_id')->nullable();
            
            // Business type
            $table->enum('type', ['corporate', 'franchise', 'independent'])->default('independent');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            
            // Owner
            $table->foreignId('owner_id')->constrained('users')->onDelete('restrict');
            
            // Contact information
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            
            // Address (headquarters)
            $table->string('address')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country', 2)->default('CL');
            $table->string('postal_code')->nullable();
            
            // Business settings
            $table->string('currency', 3)->default('CLP');
            $table->string('timezone')->default('America/Santiago');
            $table->string('locale')->default('es_CL');
            $table->json('settings')->nullable(); // Business-specific settings
            
            // Subscription/Plan
            $table->string('subscription_tier')->default('basic'); // basic, pro, enterprise
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            
            // Features and limits
            $table->json('features')->nullable(); // Enabled features for this business
            $table->json('limits')->nullable(); // Resource limits (locations, users, etc.)
            
            // Branding
            $table->string('logo_url')->nullable();
            $table->string('primary_color')->nullable();
            $table->string('secondary_color')->nullable();
            
            // Additional metadata
            $table->json('metadata')->nullable();
            $table->boolean('is_demo')->default(false);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('slug');
            $table->index('status');
            $table->index('type');
            $table->index('owner_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};