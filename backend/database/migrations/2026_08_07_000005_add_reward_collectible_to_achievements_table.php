<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mesma ideia da recompensa de missão: uma conquista também pode
     * conceder um colecionável (tipicamente um acessório de avatar).
     */
    public function up(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            $table->foreignId('reward_collectible_item_id')->nullable()->after('experience_reward')
                ->constrained('collectible_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reward_collectible_item_id');
        });
    }
};
