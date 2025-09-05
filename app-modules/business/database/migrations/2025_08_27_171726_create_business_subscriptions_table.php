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
        Schema::create('business_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            
            // Plan information
            $table->string('plan_id'); // basic, pro, enterprise
            $table->string('plan_name');
            $table->integer('price'); // Stored in minor units (cents, fils, etc.)
            $table->string('currency', 3)->default('CLP');
            $table->enum('billing_cycle', ['monthly', 'yearly', 'lifetime'])->default('monthly');
            
            // Status
            $table->enum('status', ['active', 'cancelled', 'expired', 'past_due'])->default('active');
            
            // Dates
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            
            // Payment information
            $table->string('payment_method')->nullable();
            $table->json('payment_metadata')->nullable();
            $table->timestamp('last_payment_at')->nullable();
            $table->timestamp('next_payment_at')->nullable();
            
            // Usage tracking
            $table->json('usage_limits')->nullable(); // Plan limits
            $table->json('current_usage')->nullable(); // Current usage metrics
            
            // Additional data
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('status');
            $table->index('plan_id');
            $table->index(['business_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_subscriptions');
    }
};