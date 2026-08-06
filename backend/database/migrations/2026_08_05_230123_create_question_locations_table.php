<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete()->unique();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            // 0 = usa as faixas de pontuação por distância configuráveis (ver seção 14 do brief)
            $table->unsignedInteger('accepted_radius_meters')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_locations');
    }
};
