<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->string('description')->nullable();
            $table->string('icon', 60)->nullable();
            // first_mission_completed, correct_answers_count, missions_without_hints, streak_days, ...
            $table->string('rule_type', 60);
            $table->json('rule_value')->nullable();
            $table->unsignedInteger('experience_reward')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achievements');
    }
};
