<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fix the unique constraint on menu_items table.
     * Change from unique(['menu_id', 'item_id']) to unique(['menu_section_id', 'item_id'])
     * This allows the same item to appear in multiple sections but prevents duplicates within a section.
     */
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            // Drop the existing unique constraint
            $table->dropUnique(['menu_id', 'item_id']);
            
            // Add new unique constraint for section + item
            // This ensures no duplicate items within the same section
            $table->unique(['menu_section_id', 'item_id'], 'menu_items_section_item_unique');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            // Drop the new constraint
            $table->dropUnique('menu_items_section_item_unique');
            
            // Restore the original constraint
            $table->unique(['menu_id', 'item_id']);
        });
    }
};