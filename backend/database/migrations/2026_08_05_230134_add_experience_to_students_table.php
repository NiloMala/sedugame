<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Total de XP acumulado. Nível atual é sempre derivado daqui via Level::forExperience(),
            // nunca guardado como FK separada — evita os dois ficarem dessincronizados.
            $table->unsignedInteger('experience')->default(0)->after('status');
            $table->unsignedSmallInteger('streak_days')->default(0)->after('experience');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['experience', 'streak_days']);
        });
    }
};
