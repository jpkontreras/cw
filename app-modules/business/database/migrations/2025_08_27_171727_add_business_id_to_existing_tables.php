<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Phase 1: Add optional business_id to existing tables without breaking changes
     */
    public function up(): void
    {
        // Add business_id to locations
        if (Schema::hasTable('locations') && !Schema::hasColumn('locations', 'business_id')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->foreignId('business_id')->nullable()->after('id')->constrained()->onDelete('cascade');
                $table->index(['business_id', 'code']);
            });
        }

        // Add business_id to items
        if (Schema::hasTable('items') && !Schema::hasColumn('items', 'business_id')) {
            Schema::table('items', function (Blueprint $table) {
                $table->foreignId('business_id')->nullable()->after('id')->constrained()->onDelete('cascade');
                $table->index(['business_id', 'sku']);
            });
        }

        // Add business_id to menus
        if (Schema::hasTable('menus') && !Schema::hasColumn('menus', 'business_id')) {
            Schema::table('menus', function (Blueprint $table) {
                $table->foreignId('business_id')->nullable()->after('id')->constrained()->onDelete('cascade');
                $table->index('business_id');
            });
        }

        // Add business_id to staff_members
        if (Schema::hasTable('staff_members') && !Schema::hasColumn('staff_members', 'business_id')) {
            Schema::table('staff_members', function (Blueprint $table) {
                $table->foreignId('business_id')->nullable()->after('id')->constrained()->onDelete('cascade');
                $table->index('business_id');
            });
        }

        // Add business_id to orders
        if (Schema::hasTable('orders') && !Schema::hasColumn('orders', 'business_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreignId('business_id')->nullable()->after('id')->constrained()->onDelete('cascade');
                $table->index(['business_id', 'order_number']);
            });
        }

        // Add business_id to offers
        if (Schema::hasTable('offers') && !Schema::hasColumn('offers', 'business_id')) {
            Schema::table('offers', function (Blueprint $table) {
                $table->foreignId('business_id')->nullable()->after('id')->constrained()->onDelete('cascade');
                $table->index('business_id');
            });
        }

        // Add business_id to taxonomies
        if (Schema::hasTable('taxonomies') && !Schema::hasColumn('taxonomies', 'business_id')) {
            Schema::table('taxonomies', function (Blueprint $table) {
                $table->foreignId('business_id')->nullable()->after('id')->constrained()->onDelete('cascade');
                $table->index(['business_id', 'type']);
            });
        }

        // Add business_id to settings
        if (Schema::hasTable('settings') && !Schema::hasColumn('settings', 'business_id')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->foreignId('business_id')->nullable()->after('id')->constrained()->onDelete('cascade');
                $table->dropUnique(['key']);
                $table->unique(['business_id', 'key']);
            });
        }

        // Add current_business_id to users for tracking which business they're currently working in
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'current_business_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('current_business_id')->nullable()->constrained('businesses')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove business_id from all tables
        $tables = [
            'locations', 'items', 'menus', 'staff_members', 
            'orders', 'offers', 'taxonomies', 'settings'
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'business_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropForeign(['business_id']);
                    $table->dropColumn('business_id');
                });
            }
        }

        // Special handling for settings table to restore unique constraint
        if (Schema::hasTable('settings') && !Schema::hasColumn('settings', 'business_id')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->unique(['key']);
            });
        }

        // Remove current_business_id from users
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'current_business_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['current_business_id']);
                $table->dropColumn('current_business_id');
            });
        }
    }
};