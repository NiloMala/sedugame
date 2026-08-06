<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->restrictOnDelete();
            $table->text('answer_text')->nullable();
            $table->foreignId('selected_option_id')->nullable()->constrained('question_options')->nullOnDelete();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            // calculado via Haversine no momento da correção (ver docs/02-database-mysql.md)
            $table->decimal('distance_meters', 10, 2)->nullable();
            $table->boolean('is_correct')->nullable();
            $table->unsignedInteger('score')->default(0);
            $table->unsignedInteger('time_spent_seconds')->default(0);
            $table->unsignedTinyInteger('hints_used')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempt_answers');
    }
};
