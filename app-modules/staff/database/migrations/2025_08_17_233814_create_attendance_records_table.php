<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->dateTime('clock_in_time');
            $table->dateTime('clock_out_time')->nullable();
            $table->enum('clock_in_method', ['biometric', 'pin', 'mobile', 'manual', 'card', 'facial'])
                ->default('manual');
            $table->enum('clock_out_method', ['biometric', 'pin', 'mobile', 'manual', 'card', 'facial'])
                ->nullable();
            $table->json('clock_in_location')->nullable(); // GPS coordinates
            $table->json('clock_out_location')->nullable();
            $table->dateTime('break_start')->nullable();
            $table->dateTime('break_end')->nullable();
            $table->integer('overtime_minutes')->default(0);
            $table->enum('status', ['present', 'late', 'absent', 'holiday', 'leave', 'half_day'])
                ->default('present');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['staff_member_id', 'clock_in_time']);
            $table->index(['location_id', 'clock_in_time']);
            $table->index('status');
            $table->index('shift_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};