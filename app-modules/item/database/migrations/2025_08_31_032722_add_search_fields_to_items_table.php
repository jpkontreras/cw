<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // Add search-related fields if they don't exist
            if (!Schema::hasColumn('items', 'search_keywords')) {
                $table->text('search_keywords')->nullable()->after('description');
            }
            
            if (!Schema::hasColumn('items', 'order_frequency')) {
                $table->integer('order_frequency')->default(0)->after('sort_order');
            }
            
            if (!Schema::hasColumn('items', 'category')) {
                $table->string('category')->nullable()->after('type');
            }
            
            // Add indexes for better search performance
            // We'll add indexes only for new columns
            if (Schema::hasColumn('items', 'order_frequency')) {
                $table->index(['order_frequency']);
            }
            if (Schema::hasColumn('items', 'category')) {
                $table->index(['category']);
            }
        });
        
        // Create item search history table
        Schema::create('item_search_history', function (Blueprint $table) {
            $table->id();
            $table->uuid('search_id');
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at');
            
            $table->index(['search_id']);
            $table->index(['item_id']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_search_history');
        
        Schema::table('items', function (Blueprint $table) {
            // Drop indexes if they exist
            if (Schema::hasColumn('items', 'order_frequency')) {
                $table->dropIndex(['order_frequency']);
            }
            if (Schema::hasColumn('items', 'category')) {
                $table->dropIndex(['category']);
            }
            
            // Drop columns if they exist
            if (Schema::hasColumn('items', 'search_keywords')) {
                $table->dropColumn('search_keywords');
            }
            if (Schema::hasColumn('items', 'order_frequency')) {
                $table->dropColumn('order_frequency');
            }
            if (Schema::hasColumn('items', 'category')) {
                $table->dropColumn('category');
            }
        });
    }
};