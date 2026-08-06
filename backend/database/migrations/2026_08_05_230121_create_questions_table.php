<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            // NULL = questão solta no banco central, ainda não vinculada a uma etapa de missão
            $table->foreignId('mission_stage_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('skill_id')->constrained()->restrictOnDelete();
            $table->foreignId('grade_id')->constrained()->restrictOnDelete();
            $table->enum('type', [
                'single_choice', 'multiple_choice', 'true_false', 'map_location',
                'ordering', 'matching', 'fill_blank', 'short_answer',
            ]);
            $table->text('statement');
            $table->text('explanation')->nullable();
            $table->enum('difficulty', ['easy', 'medium', 'hard']);
            $table->unsignedInteger('max_score')->default(1000);
            $table->unsignedSmallInteger('time_limit_seconds')->nullable();
            $table->enum('status', ['private', 'school', 'network', 'official', 'archived'])->default('private');
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
