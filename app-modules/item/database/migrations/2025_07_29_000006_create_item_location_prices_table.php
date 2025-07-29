<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_location_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_variant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->index(); // Foreign key to location module
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('CLP');
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->json('available_days')->nullable(); // ['monday', 'tuesday', etc.]
            $table->time('available_from_time')->nullable();
            $table->time('available_until_time')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0); // Higher priority wins in conflicts
            $table->timestamps();
            
            $table->index(['item_id', 'location_id', 'is_active']);
            $table->index(['item_variant_id', 'location_id', 'is_active']);
            $table->index(['valid_from', 'valid_until']);
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_location_prices');
    }
};