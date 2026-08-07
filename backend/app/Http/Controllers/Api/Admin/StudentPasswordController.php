<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Admin\Concerns\ScopesToSchool;
use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StudentPasswordController extends Controller
{
    use ScopesToSchool;

    /**
     * POST /api/admin/students/{student}/reset-password
     *
     * Admin (school_admin/department_admin) reseta a senha do aluno de volta
     * para a senha padrão configurada (STUDENT_DEFAULT_PASSWORD). O aluno
     * troca depois de logar, se quiser.
     */
    public function __invoke(Request $request, Student $student)
    {
        // Achado 2026-08-06: faltava esse check — o middleware de rota só garante
        // "é algum tipo de admin", sem isso um school_admin de QUALQUER escola
        // conseguia resetar senha de aluno de OUTRA escola.
        $this->assertSchoolAccess($request, $student->school_id);

        $student->loadMissing('user');

        $student->user->forceFill([
            'password' => Hash::make(config('auth.student_default_password')),
        ])->save();

        // TODO (Sprint 4): registrar em audit_logs (action: password_reset, entity: student).

        return response()->json([
            'data' => [
                'message' => 'Senha do aluno resetada para o padrão da rede.',
            ],
        ]);
    }
}
