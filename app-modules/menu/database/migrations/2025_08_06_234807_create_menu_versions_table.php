<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->integer('version_number');
            $table->string('version_name')->nullable();
            $table->json('snapshot'); // Complete menu structure at this version
            $table->string('change_type'); // created, updated, published, archived
            $table->text('change_description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable(); // User who created version
            $table->dateTime('published_at')->nullable();
            $table->dateTime('archived_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->unique(['menu_id', 'version_number']);
            $table->index('menu_id');
            $table->index('version_number');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_versions');
    }
};