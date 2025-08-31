<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('query');
            $table->json('types')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            
            $table->index(['query']);
            $table->index(['created_at']);
            $table->index(['user_id']);
        });
        
        Schema::create('search_selections', function (Blueprint $table) {
            $table->id();
            $table->uuid('search_id');
            $table->string('entity_type');
            $table->string('entity_id');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at');
            
            $table->foreign('search_id')->references('id')->on('search_logs')->cascadeOnDelete();
            $table->index(['search_id']);
            $table->index(['entity_type', 'entity_id']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_selections');
        Schema::dropIfExists('search_logs');
    }
};