<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_members', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->json('address')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->date('hire_date');
            $table->string('national_id');
            $table->json('emergency_contacts')->nullable();
            $table->json('bank_details')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended', 'terminated', 'on_leave'])
                ->default('active');
            $table->json('metadata')->nullable();
            $table->timestamp('terminated_at')->nullable();
            $table->string('profile_photo_url')->nullable();
            $table->timestamps();
            
            $table->index('status');
            $table->index('hire_date');
            $table->index('employee_code');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_members');
    }
};