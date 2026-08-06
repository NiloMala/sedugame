<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->string('name', 60);
            $table->foreignId('grade_id')->constrained()->restrictOnDelete();
            $table->foreignId('school_year_id')->constrained()->restrictOnDelete();
            $table->enum('shift', ['morning', 'afternoon', 'night', 'full']);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
