<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration adds ALL foreign keys for the order module tables.
     * It runs AFTER all dependent tables (users, locations, businesses, items, menus, staff) are created.
     */
    public function up(): void
    {
        // 1. Foreign keys for orders table
        Schema::table('orders', function (Blueprint $table) {
            // User who created the order (nullable for guest orders)
            if (Schema::hasTable('users')) {
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            }
            
            // Location where order was placed
            if (Schema::hasTable('locations')) {
                $table->foreign('location_id')->references('id')->on('locations')->restrictOnDelete();
            }
            
            // Waiter/staff who handled the order
            if (Schema::hasTable('staff_members')) {
                $table->foreign('waiter_id')->references('id')->on('staff_members')->nullOnDelete();
            }
            
            // Menu reference (nullable)
            if (Schema::hasTable('menus')) {
                $table->foreign('menu_id')->references('id')->on('menus')->nullOnDelete();
            }
        });

        // 2. Foreign keys for order_items table
        Schema::table('order_items', function (Blueprint $table) {
            // Order reference (internal FK)
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            
            // Item reference
            if (Schema::hasTable('items')) {
                $table->foreign('item_id')->references('id')->on('items')->restrictOnDelete();
            }
            
            // Menu section reference (nullable)
            if (Schema::hasTable('menu_sections')) {
                $table->foreign('menu_section_id')->references('id')->on('menu_sections')->nullOnDelete();
            }
            
            // Menu item reference (nullable)
            if (Schema::hasTable('menu_items')) {
                $table->foreign('menu_item_id')->references('id')->on('menu_items')->nullOnDelete();
            }
        });

        // 3. Foreign keys for order_status_history table
        Schema::table('order_status_history', function (Blueprint $table) {
            // Order reference (internal FK)
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            
            // User who changed the status
            if (Schema::hasTable('users')) {
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            }
        });

        // 4. Foreign keys for payment_transactions table
        Schema::table('payment_transactions', function (Blueprint $table) {
            // Order reference (internal FK)
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
        });

        // 5. Foreign keys for order_search_history table
        Schema::table('order_search_history', function (Blueprint $table) {
            // Order reference (internal FK)
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            
            // User who performed the search
            if (Schema::hasTable('users')) {
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            }
        });

        // 6. Foreign keys for order_promotions table
        Schema::table('order_promotions', function (Blueprint $table) {
            // Order reference (internal FK)
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
        });

        // 7. Foreign keys for order_item_modifiers table
        Schema::table('order_item_modifiers', function (Blueprint $table) {
            // Order item reference (internal FK)
            $table->foreign('order_item_id')->references('id')->on('order_items')->cascadeOnDelete();
        });

        // 8. Foreign keys for order_sessions table
        Schema::table('order_sessions', function (Blueprint $table) {
            // Location reference
            if (Schema::hasTable('locations')) {
                $table->foreign('location_id')->references('id')->on('locations')->cascadeOnDelete();
            }
            
            // Business reference
            if (Schema::hasTable('businesses')) {
                $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            }
            
            // User reference (nullable for guest sessions)
            if (Schema::hasTable('users')) {
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys from order_sessions
        Schema::table('order_sessions', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropForeign(['business_id']);
            $table->dropForeign(['user_id']);
        });

        // Drop foreign keys from order_item_modifiers
        Schema::table('order_item_modifiers', function (Blueprint $table) {
            $table->dropForeign(['order_item_id']);
        });

        // Drop foreign keys from order_promotions
        Schema::table('order_promotions', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
        });

        // Drop foreign keys from order_search_history
        Schema::table('order_search_history', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropForeign(['user_id']);
        });

        // Drop foreign keys from payment_transactions
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
        });

        // Drop foreign keys from order_status_history
        Schema::table('order_status_history', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropForeign(['user_id']);
        });

        // Drop foreign keys from order_items
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropForeign(['item_id']);
            $table->dropForeign(['menu_section_id']);
            $table->dropForeign(['menu_item_id']);
        });

        // Drop foreign keys from orders
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['location_id']);
            $table->dropForeign(['waiter_id']);
            $table->dropForeign(['menu_id']);
        });
    }
};