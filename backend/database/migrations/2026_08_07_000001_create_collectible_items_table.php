<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Coleções (brief seção 18) — itens digitais desbloqueáveis, nunca
     * comprados com dinheiro real. `avatar_accessory` é uma categoria extra
     * (não estava no brief original): itens dessa categoria podem ser
     * equipados no avatar do aluno, ver `students.equipped_accessory_id`.
     */
    public function up(): void
    {
        Schema::create('collectible_items', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->enum('category', [
                'monument', 'animal', 'biome', 'map', 'historical_figure',
                'coat_of_arms', 'flag', 'postcard', 'artifact', 'culture', 'avatar_accessory',
            ]);
            $table->string('image_url')->nullable();
            // ícone lucide-react p/ renderizar sem depender de arquivo de imagem (usado sobretudo em avatar_accessory)
            $table->string('icon', 60)->nullable();
            $table->enum('rarity', ['common', 'rare', 'epic'])->default('common');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collectible_items');
    }
};
