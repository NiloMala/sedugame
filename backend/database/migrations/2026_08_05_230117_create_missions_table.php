<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('missions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            $table->string('slug', 160);
            $table->text('description')->nullable();
            $table->text('narrative')->nullable();
            $table->string('objective')->nullable();
            $table->unsignedSmallInteger('order')->default(0);
            $table->enum('difficulty', ['easy', 'medium', 'hard']);
            $table->unsignedSmallInteger('estimated_minutes')->nullable();
            $table->unsignedInteger('max_score')->default(0);
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->json('unlock_rule')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('missions');
    }
};
