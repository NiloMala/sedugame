<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mídia é exibida na área principal de cada ETAPA da missão (mapa/imagem/
     * panorama daquele passo específico), não da missão como um todo — o
     * pivot `mission_media` do Sprint 1 modelou no nível errado. Mantido
     * (sem uso) para não quebrar nada que já dependa dele; a partir daqui o
     * gameplay usa este novo pivot por etapa.
     */
    public function up(): void
    {
        Schema::create('mission_stage_media', function (Blueprint $table) {
            $table->foreignId('mission_stage_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('order')->default(0);
            $table->primary(['mission_stage_id', 'media_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_stage_media');
    }
};
