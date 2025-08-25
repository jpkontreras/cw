<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxonomy_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('taxonomy_id')->constrained()->cascadeOnDelete();
            $table->string('key', 50);
            $table->string('value');
            $table->string('type', 20)->default('string'); // string, number, boolean, json
            $table->timestamps();
            
            $table->unique(['taxonomy_id', 'key']);
            $table->index(['key', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxonomy_attributes');
    }
};