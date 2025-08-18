<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->integer('break_duration')->default(30); // minutes
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled', 'no_show'])
                ->default('scheduled');
            $table->dateTime('actual_start')->nullable();
            $table->dateTime('actual_end')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('staff_members')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('staff_members')->nullOnDelete();
            $table->timestamps();
            
            $table->index(['staff_member_id', 'start_time']);
            $table->index(['location_id', 'start_time']);
            $table->index('status');
            $table->index(['start_time', 'end_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};