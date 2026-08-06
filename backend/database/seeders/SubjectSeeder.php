<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    /**
     * As 4 disciplinas do brief (seção 1). Ícones usam nomes do lucide-react,
     * já que é o pacote de ícones que o frontend Next.js usa.
     */
    public function run(): void
    {
        $subjects = [
            ['name' => 'Matemática', 'slug' => 'matematica', 'icon' => 'calculator', 'color' => '#6D28D9'],
            ['name' => 'Língua Portuguesa', 'slug' => 'lingua-portuguesa', 'icon' => 'book-open', 'color' => '#DC2626'],
            ['name' => 'Geografia', 'slug' => 'geografia', 'icon' => 'map', 'color' => '#16A34A'],
            ['name' => 'História', 'slug' => 'historia', 'icon' => 'landmark', 'color' => '#D97706'],
        ];

        foreach ($subjects as $subject) {
            Subject::updateOrCreate(['slug' => $subject['slug']], $subject);
        }
    }
}
