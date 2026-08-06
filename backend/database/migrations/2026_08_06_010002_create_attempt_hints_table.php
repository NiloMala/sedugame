<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registra qual pista foi usada em qual questão dentro de uma tentativa —
     * necessário pra somar a penalidade correta na hora de pontuar a resposta
     * (a pista já revela o conteúdo na hora, mas o desconto só é aplicado
     * quando a questão é respondida).
     */
    public function up(): void
    {
        Schema::create('attempt_hints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hint_id')->constrained()->cascadeOnDelete();
            $table->timestamp('used_at');

            $table->unique(['attempt_id', 'hint_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempt_hints');
    }
};
