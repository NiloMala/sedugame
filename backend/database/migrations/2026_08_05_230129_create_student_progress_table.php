<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mission_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->unsignedInteger('best_score')->default(0);
            $table->unsignedSmallInteger('attempts_count')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'mission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_progress');
    }
};
