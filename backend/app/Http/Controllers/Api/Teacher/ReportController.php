<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Exports\ClassReportExport;
use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

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
     * GET /api/teacher/reports/class/{classId}/export?format=csv|pdf|xlsx
     */
    public function export(Request $request, SchoolClass $class): Response
    {
        $this->assertOwnClass($request, $class);

        $format = $request->query('format', 'csv');
        abort_unless(in_array($format, ['csv', 'pdf', 'xlsx'], true), 422, 'Formato de exportação inválido. Use csv, pdf ou xlsx.');

        $rows = $this->classReportRows($class);
        $filename = "relatorio-turma-{$class->id}";

        return match ($format) {
            'csv' => $this->exportCsv($rows, $filename),
            'pdf' => $this->exportPdf($class, $rows, $filename),
            'xlsx' => $this->exportXlsx($class, $rows, $filename),
        };
    }

    /**
     * Uma linha por aluno da turma — usada pelos 3 formatos de export, pra
     * garantir que csv/pdf/xlsx nunca fiquem com dados divergentes entre si.
     */
    private function classReportRows(SchoolClass $class): Collection
    {
        return $class->students()->with('user')->get()->map(function (Student $student) {
            $completedIds = Attempt::where('student_id', $student->id)->where('status', 'completed')->pluck('id');

            return [
                'name' => $student->user->name,
                'registration_number' => $student->registration_number,
                'experience' => $student->experience,
                'level' => $student->level()?->name,
                'completed_missions' => $completedIds->count(),
                'average_score' => $completedIds->isEmpty() ? 0 : round(Attempt::whereIn('id', $completedIds)->avg('score')),
            ];
        });
    }

    private function exportCsv(Collection $rows, string $filename): Response
    {
        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Aluno', 'RA', 'XP', 'Nível', 'Missões concluídas', 'Pontuação média']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['name'], $row['registration_number'], $row['experience'],
                    $row['level'], $row['completed_missions'], $row['average_score'],
                ]);
            }

            fclose($handle);
        }, "{$filename}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function exportPdf(SchoolClass $class, Collection $rows, string $filename): Response
    {
        return Pdf::loadView('reports.class-export', ['class' => $class, 'rows' => $rows])
            ->setPaper('a4', 'landscape')
            ->download("{$filename}.pdf");
    }

    private function exportXlsx(SchoolClass $class, Collection $rows, string $filename): Response
    {
        return Excel::download(new ClassReportExport($class->name, $rows), "{$filename}.xlsx");
    }

    private function assertOwnClass(Request $request, SchoolClass $class): void
    {
        $teacher = $request->user()->teacher()->first();
        abort_unless($teacher && $teacher->classes()->where('classes.id', $class->id)->exists(), 403, 'Você não leciona nesta turma.');
    }
}
