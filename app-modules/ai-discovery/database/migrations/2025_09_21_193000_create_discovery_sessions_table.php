<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discovery_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('item_id')->nullable();
            $table->string('session_uuid')->unique();
            $table->json('restaurant_context'); // cuisine_type, location, price_tier
            $table->json('conversation_history');
            $table->json('extracted_data'); // variants, modifiers, metadata
            $table->json('confidence_scores');
            $table->enum('status', ['active', 'completed', 'abandoned'])->default('active');
            $table->integer('messages_count')->default(0);
            $table->integer('tokens_used')->default(0);
            $table->timestamps();

            $table->index('user_id');
            $table->index('item_id');
            $table->index('status');
            $table->index('session_uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discovery_sessions');
    }
};