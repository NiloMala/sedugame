<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            LevelSeeder::class,
            GradeSeeder::class,
            SubjectSeeder::class,
            SchoolYearSeeder::class,
        ]);

        $school = School::updateOrCreate(
            ['code' => 'CARAGUA-001'],
            [
                'name' => 'EMEF Modelo — Caraguatatuba',
                'city' => 'Caraguatatuba',
                'state' => 'SP',
                'status' => 'active',
            ]
        );

        // Login de teste local — NÃO usar em produção. Trocar a senha (ou remover o
        // usuário) antes do primeiro deploy real. Ver docs/01-arquitetura-e-plano.md.
        if (app()->environment('local')) {
            User::updateOrCreate(
                ['email' => 'admin@expedicaodosaber.local'],
                [
                    'name' => 'Admin (dev)',
                    'password' => 'password',
                    'role_id' => Role::where('slug', 'super_admin')->value('id'),
                    'school_id' => $school->id,
                    'status' => 'active',
                ]
            );
        }

        // Conteúdo de demonstração (não é o volume final de produção — ver
        // comentário no topo de cada seeder). 3 campanhas ao todo, pra dar
        // volume de apresentação sem inventar fato específico arriscado.
        $this->call(CaraguatatubaCampaignSeeder::class);
        $this->call(DemoCampaignsExpansionSeeder::class);
    }
}
