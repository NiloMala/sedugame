<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->string('slug', 160)->unique();
            $table->text('description')->nullable();
            $table->string('cover_image_url')->nullable();
            $table->foreignId('primary_subject_id')->constrained('subjects')->restrictOnDelete();
            $table->foreignId('grade_id')->constrained()->restrictOnDelete();
            $table->enum('difficulty', ['easy', 'medium', 'hard']);
            $table->enum('status', ['draft', 'in_review', 'approved', 'published', 'archived'])->default('draft');
            $table->enum('visibility', ['private', 'school', 'network'])->default('private');
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->unsignedSmallInteger('estimated_minutes')->nullable();
            $table->unsignedInteger('max_score')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
