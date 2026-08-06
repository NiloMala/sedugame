<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * avatar_base: código de um personagem ilustrado pré-definido (ver
     * App\Support\AvatarPresets) — NUNCA foto real enviada pelo aluno
     * (LGPD/ECA: dado de criança/adolescente, evita todo o problema de
     * moderação de imagem de menor).
     * equipped_accessory_id: acessório de coleção (category=avatar_accessory)
     * atualmente equipado — precisa estar em student_collectibles pra valer.
     * last_activity_date: base pro cálculo de streak_days (sequência de dias
     * jogando, seção 16 do brief — a coluna streak_days já existia desde o
     * Sprint 1 mas nunca foi calculada).
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('avatar_base', 40)->default('compass')->after('streak_days');
            $table->foreignId('equipped_accessory_id')->nullable()->after('avatar_base')
                ->constrained('collectible_items')->nullOnDelete();
            $table->date('last_activity_date')->nullable()->after('equipped_accessory_id');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('equipped_accessory_id');
            $table->dropColumn(['avatar_base', 'last_activity_date']);
        });
    }
};
