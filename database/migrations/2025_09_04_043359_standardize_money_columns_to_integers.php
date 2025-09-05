<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Convert all money columns to integers (storing cents)
     * for consistent money handling across the system.
     */
    public function up(): void
    {
        // Convert order_items table from decimal to integer (multiply by 100 to convert to cents)
        Schema::table('order_items', function (Blueprint $table) {
            // First, create temporary columns
            $table->integer('unit_price_cents')->nullable();
            $table->integer('total_price_cents')->nullable();
        });

        // Convert existing decimal values to cents
        DB::statement('UPDATE order_items SET unit_price_cents = CAST(unit_price * 100 AS INTEGER)');
        DB::statement('UPDATE order_items SET total_price_cents = CAST(total_price * 100 AS INTEGER)');

        // Drop old columns and rename new ones
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['unit_price', 'total_price']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->renameColumn('unit_price_cents', 'unit_price');
            $table->renameColumn('total_price_cents', 'total_price');
        });

        // Make columns not nullable
        Schema::table('order_items', function (Blueprint $table) {
            $table->integer('unit_price')->nullable(false)->change();
            $table->integer('total_price')->nullable(false)->change();
        });

        // Check if items table exists and has price columns
        if (Schema::hasTable('items')) {
            $columns = Schema::getColumnListing('items');
            
            if (in_array('base_price', $columns)) {
                Schema::table('items', function (Blueprint $table) {
                    $table->integer('base_price_cents')->nullable();
                });

                // Convert existing decimal values to cents
                DB::statement('UPDATE items SET base_price_cents = CAST(base_price * 100 AS INTEGER)');

                Schema::table('items', function (Blueprint $table) {
                    $table->dropColumn('base_price');
                });

                Schema::table('items', function (Blueprint $table) {
                    $table->renameColumn('base_price_cents', 'base_price');
                });

                Schema::table('items', function (Blueprint $table) {
                    $table->integer('base_price')->nullable(false)->default(0)->change();
                });
            }

            if (in_array('sale_price', $columns)) {
                Schema::table('items', function (Blueprint $table) {
                    $table->integer('sale_price_cents')->nullable();
                });

                DB::statement('UPDATE items SET sale_price_cents = CAST(sale_price * 100 AS INTEGER) WHERE sale_price IS NOT NULL');

                Schema::table('items', function (Blueprint $table) {
                    $table->dropColumn('sale_price');
                });

                Schema::table('items', function (Blueprint $table) {
                    $table->renameColumn('sale_price_cents', 'sale_price');
                });
            }

            if (in_array('cost', $columns)) {
                Schema::table('items', function (Blueprint $table) {
                    $table->integer('cost_cents')->nullable();
                });

                DB::statement('UPDATE items SET cost_cents = CAST(cost * 100 AS INTEGER) WHERE cost IS NOT NULL');

                Schema::table('items', function (Blueprint $table) {
                    $table->dropColumn('cost');
                });

                Schema::table('items', function (Blueprint $table) {
                    $table->renameColumn('cost_cents', 'cost');
                });
            }
        }

        // Check if menu_items table exists
        if (Schema::hasTable('menu_items')) {
            $columns = Schema::getColumnListing('menu_items');
            
            if (in_array('price', $columns)) {
                Schema::table('menu_items', function (Blueprint $table) {
                    $table->integer('price_cents')->nullable();
                });

                DB::statement('UPDATE menu_items SET price_cents = CAST(price * 100 AS INTEGER)');

                Schema::table('menu_items', function (Blueprint $table) {
                    $table->dropColumn('price');
                });

                Schema::table('menu_items', function (Blueprint $table) {
                    $table->renameColumn('price_cents', 'price');
                });

                Schema::table('menu_items', function (Blueprint $table) {
                    $table->integer('price')->nullable(false)->default(0)->change();
                });
            }
        }

        // Check if payment_transactions table exists
        if (Schema::hasTable('payment_transactions')) {
            $columns = Schema::getColumnListing('payment_transactions');
            
            if (in_array('amount', $columns)) {
                // Check if it's already integer
                $columnType = DB::select("SELECT data_type FROM information_schema.columns WHERE table_name = 'payment_transactions' AND column_name = 'amount'")[0]->data_type ?? null;
                
                if ($columnType !== 'integer') {
                    Schema::table('payment_transactions', function (Blueprint $table) {
                        $table->integer('amount_cents')->nullable();
                    });

                    DB::statement('UPDATE payment_transactions SET amount_cents = CAST(amount * 100 AS INTEGER)');

                    Schema::table('payment_transactions', function (Blueprint $table) {
                        $table->dropColumn('amount');
                    });

                    Schema::table('payment_transactions', function (Blueprint $table) {
                        $table->renameColumn('amount_cents', 'amount');
                    });

                    Schema::table('payment_transactions', function (Blueprint $table) {
                        $table->integer('amount')->nullable(false)->change();
                    });
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert order_items back to decimal
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('unit_price_decimal', 10, 2)->nullable();
            $table->decimal('total_price_decimal', 10, 2)->nullable();
        });

        DB::statement('UPDATE order_items SET unit_price_decimal = unit_price / 100.0');
        DB::statement('UPDATE order_items SET total_price_decimal = total_price / 100.0');

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['unit_price', 'total_price']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->renameColumn('unit_price_decimal', 'unit_price');
            $table->renameColumn('total_price_decimal', 'total_price');
        });

        // Revert items table if it exists
        if (Schema::hasTable('items')) {
            $columns = Schema::getColumnListing('items');
            
            if (in_array('base_price', $columns)) {
                Schema::table('items', function (Blueprint $table) {
                    $table->decimal('base_price_decimal', 10, 2)->nullable();
                });

                DB::statement('UPDATE items SET base_price_decimal = base_price / 100.0');

                Schema::table('items', function (Blueprint $table) {
                    $table->dropColumn('base_price');
                });

                Schema::table('items', function (Blueprint $table) {
                    $table->renameColumn('base_price_decimal', 'base_price');
                });
            }

            // Similar for other columns...
        }

        // Similar reversions for other tables...
    }
};