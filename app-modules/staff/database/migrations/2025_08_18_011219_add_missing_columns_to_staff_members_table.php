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
        Schema::table('staff_members', function (Blueprint $table) {
            $table->string('tax_id')->nullable()->after('national_id');
            $table->decimal('hourly_rate', 8, 2)->nullable()->after('bank_details');
            $table->decimal('monthly_salary', 10, 2)->nullable()->after('hourly_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff_members', function (Blueprint $table) {
            $table->dropColumn(['tax_id', 'hourly_rate', 'monthly_salary']);
        });
    }
};
