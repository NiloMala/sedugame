<?php

namespace Database\Seeders;

use App\Models\Grade;
use Illuminate\Database\Seeder;

class GradeSeeder extends Seeder
{
    /**
     * MVP atende só 6º e 7º ano (brief seção 4/50). 8º/9º e demais entram na
     * expansão da Fase 2 — não semeados agora para não sugerir suporte que não existe.
     */
    public function run(): void
    {
        $grades = [
            ['order' => 6, 'name' => '6º ano', 'code' => '6EF2', 'education_level' => 'EF2'],
            ['order' => 7, 'name' => '7º ano', 'code' => '7EF2', 'education_level' => 'EF2'],
        ];

        foreach ($grades as $grade) {
            Grade::updateOrCreate(['code' => $grade['code']], $grade);
        }
    }
}
