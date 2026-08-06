<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_classes', function (Blueprint $table) {
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->primary(['teacher_id', 'class_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_classes');
    }
};
