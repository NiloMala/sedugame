<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\ReportService;
use Illuminate\Http\Request;

/**
 * Painéis da Escola/Secretaria (brief seções 28-29). Autorização:
 * coordinator/director/school_admin só a própria escola; department_admin/
 * super_admin qualquer escola + rede toda (ver tabela em docs/03-api-contract.md).
 */
class ReportController extends Controller
{
    public function network(Request $request, ReportService $reports)
    {
        abort_unless(in_array($request->user()->role->slug, ['department_admin', 'super_admin'], true), 403);

        return ['data' => $reports->networkIndicators()];
    }

    public function school(Request $request, School $school, ReportService $reports)
    {
        $this->authorizeSchool($request, $school->id);

        return ['data' => $reports->schoolIndicators($school)];
    }

    public function schoolClass(Request $request, SchoolClass $class, ReportService $reports)
    {
        $this->authorizeSchool($request, $class->school_id);

        $attemptIds = Attempt::whereIn('student_id', Student::where('class_id', $class->id)->pluck('id'))->pluck('id');

        return ['data' => $reports->attemptStats($attemptIds) + [
            'class' => ['id' => $class->id, 'name' => $class->name],
            'critical_skills' => $reports->criticalSkills($attemptIds),
        ]];
    }

    public function student(Request $request, Student $student, ReportService $reports)
    {
        $this->authorizeSchool($request, $student->school_id);

        $attemptIds = Attempt::where('student_id', $student->id)->pluck('id');

        return ['data' => $reports->attemptStats($attemptIds) + [
            'student' => ['id' => $student->id, 'name' => $student->user->name],
            'critical_skills' => $reports->criticalSkills($attemptIds),
        ]];
    }

    private function authorizeSchool(Request $request, int $schoolId): void
    {
        $role = $request->user()->role->slug;
        if (in_array($role, ['department_admin', 'super_admin'], true)) {
            return;
        }

        abort_unless(
            in_array($role, ['coordinator', 'director', 'school_admin'], true) && $request->user()->school_id === $schoolId,
            403,
            'Você só pode consultar relatórios da sua própria escola.'
        );
    }
}
