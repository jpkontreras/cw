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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('current_location_id')
                ->nullable()
                ->after('password')
                ->constrained('locations')
                ->onDelete('set null');
                
            $table->foreignId('default_location_id')
                ->nullable()
                ->after('current_location_id')
                ->constrained('locations')
                ->onDelete('set null');
                
            // Indexes for better query performance
            $table->index('current_location_id');
            $table->index('default_location_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['current_location_id']);
            $table->dropForeign(['default_location_id']);
            $table->dropColumn(['current_location_id', 'default_location_id']);
        });
    }
};