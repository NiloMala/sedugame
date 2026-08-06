<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Recompensa" já estava listada como atributo de missão no brief
     * (seção 10) mas nunca foi modelada — cada missão pode conceder um
     * colecionável temático ao ser concluída pela primeira vez.
     */
    public function up(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->foreignId('reward_collectible_item_id')->nullable()->after('unlock_rule')
                ->constrained('collectible_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reward_collectible_item_id');
        });
    }
};
