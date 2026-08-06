<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // RA - também é o identificador de login simplificado do aluno (ver AuthController)
            $table->string('registration_number', 50)->unique();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $table->date('birth_date')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
