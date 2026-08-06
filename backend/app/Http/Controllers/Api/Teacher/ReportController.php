<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function classReport(Request $request, SchoolClass $class, ReportService $reports)
    {
        $this->assertOwnClass($request, $class);

        $attemptIds = Attempt::whereIn('student_id', Student::where('class_id', $class->id)->pluck('id'))->pluck('id');

        return ['data' => $reports->attemptStats($attemptIds) + [
            'class' => ['id' => $class->id, 'name' => $class->name],
            'critical_skills' => $reports->criticalSkills($attemptIds),
        ]];
    }

    /**
     * GET /api/teacher/reports/class/{classId}/export?format=csv
     * Só CSV por enquanto — pdf/xlsx exigem escolher e instalar uma lib
     * (dompdf/maatwebsite-excel), decisão que fica pra quando for necessário.
     */
    public function export(Request $request, SchoolClass $class): StreamedResponse
    {
        $this->assertOwnClass($request, $class);

        $format = $request->query('format', 'csv');
        abort_unless($format === 'csv', 422, 'Só exportação em CSV está disponível no momento (pdf/xlsx ainda não implementados).');

        $students = $class->students()->with('user')->get();

        return response()->streamDownload(function () use ($students) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Aluno', 'RA', 'XP', 'Nível', 'Missões concluídas', 'Pontuação média']);

            foreach ($students as $student) {
                $completedIds = Attempt::where('student_id', $student->id)->where('status', 'completed')->pluck('id');
                fputcsv($handle, [
                    $student->user->name,
                    $student->registration_number,
                    $student->experience,
                    $student->level()?->name,
                    $completedIds->count(),
                    $completedIds->isEmpty() ? 0 : round(Attempt::whereIn('id', $completedIds)->avg('score')),
                ]);
            }

            fclose($handle);
        }, "relatorio-turma-{$class->id}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function assertOwnClass(Request $request, SchoolClass $class): void
    {
        $teacher = $request->user()->teacher()->first();
        abort_unless($teacher && $teacher->classes()->where('classes.id', $class->id)->exists(), 403, 'Você não leciona nesta turma.');
    }
}
