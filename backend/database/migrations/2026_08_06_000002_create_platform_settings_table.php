<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabela singleton (sempre 1 linha, id=1) com as configurações globais
     * da plataforma — nome, cores do tema, regras de pontuação (seção 43/13
     * do brief: "nome e cores deverão ser configuráveis pelo administrador").
     */
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('platform_name')->default('Expedição do Saber');
            $table->json('theme_colors')->nullable();
            $table->json('scoring_rules')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};
