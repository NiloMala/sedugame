<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `order` já existe e passa a ter dois usos contextuais por tipo de questão:
     * - ordering: posição correta final (0, 1, 2...)
     * - matching: índice do par (uma option "left" e uma "right" com o mesmo order formam o par correto)
     * `side` só é usado em questões do tipo matching.
     */
    public function up(): void
    {
        Schema::table('question_options', function (Blueprint $table) {
            $table->enum('side', ['left', 'right'])->nullable()->after('order');
        });
    }

    public function down(): void
    {
        Schema::table('question_options', function (Blueprint $table) {
            $table->dropColumn('side');
        });
    }
};
