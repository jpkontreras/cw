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
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('menu_id')->nullable()->after('location_id');
            $table->integer('menu_version')->nullable()->after('menu_id');
            
            $table->index('menu_id');
            $table->index(['menu_id', 'menu_version']);
        });
        
        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('menu_section_id')->nullable()->after('item_id');
            $table->unsignedBigInteger('menu_item_id')->nullable()->after('menu_section_id');
            
            $table->index('menu_section_id');
            $table->index('menu_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['menu_id', 'menu_version']);
            $table->dropIndex(['menu_id']);
            $table->dropColumn(['menu_id', 'menu_version']);
        });
        
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['menu_section_id']);
            $table->dropIndex(['menu_item_id']);
            $table->dropColumn(['menu_section_id', 'menu_item_id']);
        });
    }
};