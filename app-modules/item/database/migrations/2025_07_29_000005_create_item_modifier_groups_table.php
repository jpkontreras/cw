<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_modifier_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('modifier_group_id')->constrained()->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->unique(['item_id', 'modifier_group_id']);
            $table->index('item_id');
            $table->index('modifier_group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_modifier_groups');
    }
};