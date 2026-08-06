<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClassController extends Controller
{
    /**
     * GET /api/teacher/classes
     *
     * Uma linha por (turma, disciplina) — o pivot teacher_classes permite um
     * professor lecionar a mesma turma em mais de uma disciplina.
     */
    public function index(Request $request)
    {
        $teacher = $request->user()->teacher()->first();
        abort_unless($teacher, 403);

        $rows = DB::table('teacher_classes')
            ->join('classes', 'classes.id', '=', 'teacher_classes.class_id')
            ->join('grades', 'grades.id', '=', 'classes.grade_id')
            ->join('subjects', 'subjects.id', '=', 'teacher_classes.subject_id')
            ->where('teacher_classes.teacher_id', $teacher->id)
            ->select('classes.id', 'classes.name', 'classes.shift', 'grades.name as grade_name', 'subjects.id as subject_id', 'subjects.name as subject_name')
            ->get();

        $studentCounts = \App\Models\Student::whereIn('class_id', $rows->pluck('id')->unique())
            ->selectRaw('class_id, count(*) as total')->groupBy('class_id')->pluck('total', 'class_id');

        return ['data' => $rows->map(fn ($row) => [
            'id' => $row->id,
            'name' => $row->name,
            'shift' => $row->shift,
            'grade' => $row->grade_name,
            'subject' => ['id' => $row->subject_id, 'name' => $row->subject_name],
            'students_count' => $studentCounts[$row->id] ?? 0,
        ])->values()];
    }

    /**
     * GET /api/teacher/classes/{class}/students
     */
    public function students(Request $request, SchoolClass $class)
    {
        $teacher = $request->user()->teacher()->first();
        abort_unless($teacher, 403);
        abort_unless($teacher->classes()->where('classes.id', $class->id)->exists(), 403, 'Você não leciona nesta turma.');

        $students = $class->students()->with('user')->get()->map(function ($student) {
            $attemptIds = Attempt::where('student_id', $student->id)->where('status', 'completed')->pluck('id');

            return [
                'id' => $student->id,
                'name' => $student->user->name,
                'registration_number' => $student->registration_number,
                'level' => $student->level()?->name,
                'experience' => $student->experience,
                'missions_completed' => $attemptIds->count(),
                'average_score' => $attemptIds->isEmpty() ? 0 : (int) round(Attempt::whereIn('id', $attemptIds)->avg('score')),
            ];
        });

        return ['data' => $students];
    }
}
