<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Papéis fixos do sistema (brief seção 38). Não são gerenciáveis via painel —
     * permissões dentro de cada papel podem evoluir, mas a lista de papéis em si é fixa.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'Aluno', 'slug' => 'student'],
            ['name' => 'Professor', 'slug' => 'teacher'],
            ['name' => 'Coordenador Pedagógico', 'slug' => 'coordinator'],
            ['name' => 'Diretor', 'slug' => 'director'],
            ['name' => 'Administrador da Escola', 'slug' => 'school_admin'],
            ['name' => 'Administrador da Secretaria', 'slug' => 'department_admin'],
            ['name' => 'Super Administrador', 'slug' => 'super_admin'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
