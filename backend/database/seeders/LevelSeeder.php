<?php

namespace Database\Seeders;

use App\Models\Level;
use Illuminate\Database\Seeder;

class LevelSeeder extends Seeder
{
    /**
     * Níveis do brief seção 16. Faixas de XP são um ponto de partida — ajustáveis
     * pelo admin depois de observar o ritmo real de progressão dos alunos.
     */
    public function run(): void
    {
        $levels = [
            ['order' => 1, 'name' => 'Explorador Iniciante', 'minimum_experience' => 0, 'maximum_experience' => 999],
            ['order' => 2, 'name' => 'Aprendiz de Viajante', 'minimum_experience' => 1000, 'maximum_experience' => 2499],
            ['order' => 3, 'name' => 'Investigador', 'minimum_experience' => 2500, 'maximum_experience' => 4999],
            ['order' => 4, 'name' => 'Cartógrafo', 'minimum_experience' => 5000, 'maximum_experience' => 8999],
            ['order' => 5, 'name' => 'Historiador', 'minimum_experience' => 9000, 'maximum_experience' => 14999],
            ['order' => 6, 'name' => 'Mestre das Expedições', 'minimum_experience' => 15000, 'maximum_experience' => null],
        ];

        foreach ($levels as $level) {
            Level::updateOrCreate(['order' => $level['order']], $level);
        }
    }
}
