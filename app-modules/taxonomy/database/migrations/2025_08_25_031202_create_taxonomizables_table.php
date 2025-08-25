<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxonomizables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('taxonomy_id')->constrained()->cascadeOnDelete();
            $table->string('taxonomizable_type');
            $table->unsignedBigInteger('taxonomizable_id');
            $table->json('metadata')->nullable(); // Relationship-specific data
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->unique(['taxonomy_id', 'taxonomizable_id', 'taxonomizable_type'], 'unique_taxonomy_entity');
            $table->index(['taxonomizable_type', 'taxonomizable_id'], 'idx_taxonomizable_morph');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxonomizables');
    }
};