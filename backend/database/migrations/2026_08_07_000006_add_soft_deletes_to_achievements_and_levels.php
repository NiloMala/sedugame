<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `achievements` e `levels` ficaram de fora da migration
     * 2026_08_06_000001 (que aplicou soft delete aos cadastros de referência)
     * porque na época ainda não tinham CRUD admin — só seed. Agora que
     * ganham CRUD admin, precisam seguir a mesma regra do brief (seção 48):
     * exclusão administrativa é lógica, nunca hard delete. Isso importa de
     * verdade aqui porque `student_achievements`/`student_collectibles` têm
     * `cascadeOnDelete()` na FK — um hard delete apagaria o histórico de
     * conquistas já desbloqueadas por alunos.
     */
    public function up(): void
    {
        Schema::table('achievements', fn (Blueprint $table) => $table->softDeletes());
        Schema::table('levels', fn (Blueprint $table) => $table->softDeletes());
    }

    public function down(): void
    {
        Schema::table('achievements', fn (Blueprint $table) => $table->dropSoftDeletes());
        Schema::table('levels', fn (Blueprint $table) => $table->dropSoftDeletes());
    }
};
