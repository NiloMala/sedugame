<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Admin\Concerns\ScopesToSchool;
use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Vínculo professor × turma × disciplina (tabela pivot `teacher_classes`).
 * Faltava um endpoint pra isso — a atribuição só existia via Eloquent direto
 * (usado em seeders/testes); pelo painel administrativo não tinha como um
 * school_admin/department_admin atribuir professor a turma.
 */
class ClassTeacherController extends Controller
{
    use ScopesToSchool;

    public function store(Request $request, SchoolClass $class)
    {
        $this->assertSchoolAccess($request, $class->school_id);

        $data = $request->validate([
            'teacher_id' => ['required', 'exists:teachers,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
        ]);

        $teacher = Teacher::findOrFail($data['teacher_id']);
        abort_unless($teacher->school_id === $class->school_id, 422, 'O professor precisa ser da mesma escola da turma.');

        $alreadyLinked = DB::table('teacher_classes')
            ->where(['teacher_id' => $teacher->id, 'class_id' => $class->id, 'subject_id' => $data['subject_id']])
            ->exists();

        if (! $alreadyLinked) {
            $class->teachers()->attach($teacher->id, ['subject_id' => $data['subject_id']]);
        }

        return response()->json(['data' => $class->load('teachers.user', 'teachers.school')], 201);
    }

    /**
     * Sem ?subject_id, remove todas as disciplinas desse professor nessa turma
     * (um professor pode lecionar mais de uma disciplina na mesma turma —
     * a chave primária de teacher_classes é [teacher_id, class_id, subject_id]).
     */
    public function destroy(Request $request, SchoolClass $class, Teacher $teacher)
    {
        $this->assertSchoolAccess($request, $class->school_id);

        $query = DB::table('teacher_classes')->where('class_id', $class->id)->where('teacher_id', $teacher->id);

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->integer('subject_id'));
        }

        abort_if($query->count() === 0, 404, 'Vínculo não encontrado.');
        $query->delete();

        return response()->noContent();
    }
}
