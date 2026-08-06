<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            // NULL = modo individual/livre (fora de uma atividade atribuída pelo professor)
            $table->foreignId('activity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('campaign_id')->constrained()->restrictOnDelete();
            $table->foreignId('mission_id')->constrained()->restrictOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('score')->default(0);
            $table->unsignedInteger('experience')->default(0);
            $table->enum('status', ['in_progress', 'completed', 'abandoned'])->default('in_progress');
            $table->unsignedInteger('time_spent_seconds')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempts');
    }
};
