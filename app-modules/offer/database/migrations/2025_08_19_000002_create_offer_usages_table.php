<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('customer_email')->nullable();
            $table->decimal('discount_amount', 10, 2);
            $table->decimal('order_amount', 10, 2);
            $table->string('code', 50)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('used_at');
            $table->timestamps();
            
            // Indexes for performance
            $table->index('offer_id');
            $table->index('order_id');
            $table->index('customer_id');
            $table->index('customer_email');
            $table->index('used_at');
            $table->index(['offer_id', 'customer_id']);
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('offer_usages');
    }
};