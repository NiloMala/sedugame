<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Regra de negócio do brief (seção 48): exclusões administrativas devem
     * preferencialmente ser lógicas. Aplicando aos cadastros de referência que
     * ficaram de fora quando as tabelas foram criadas.
     */
    private array $tables = ['schools', 'classes', 'grades', 'subjects', 'skills', 'school_years'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->softDeletes());
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropSoftDeletes());
        }
    }
};
