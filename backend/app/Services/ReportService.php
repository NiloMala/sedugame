<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Agregações usadas pelos painéis de professor/escola/secretaria (brief
 * seções 27-29). Fica num serviço à parte porque os mesmos cálculos
 * (acurácia por habilidade, médias) são reaproveitados em vários endpoints.
 */
class ReportService
{
    /**
     * Estatísticas gerais de um conjunto de tentativas concluídas.
     */
    public function attemptStats(Collection $attemptIds): array
    {
        if ($attemptIds->isEmpty()) {
            return ['attempts_count' => 0, 'average_score' => 0, 'accuracy_percent' => 0, 'average_time_seconds' => 0];
        }

        $attempts = Attempt::whereIn('id', $attemptIds)->where('status', 'completed');
        $answers = AttemptAnswer::whereIn('attempt_id', $attemptIds);

        $totalAnswers = (clone $answers)->count();
        $correctAnswers = (clone $answers)->where('is_correct', true)->count();

        return [
            'attempts_count' => (clone $attempts)->count(),
            'average_score' => (int) round((clone $attempts)->avg('score') ?? 0),
            'accuracy_percent' => $totalAnswers > 0 ? (int) round($correctAnswers / $totalAnswers * 100) : 0,
            'average_time_seconds' => (int) round((clone $answers)->avg('time_spent_seconds') ?? 0),
        ];
    }

    /**
     * Habilidades com pior desempenho dentro de um conjunto de tentativas —
     * "habilidades críticas" citado nas seções 27-29 do brief.
     */
    public function criticalSkills(Collection $attemptIds, int $limit = 5): array
    {
        if ($attemptIds->isEmpty()) {
            return [];
        }

        return AttemptAnswer::query()
            ->whereIn('attempt_answers.attempt_id', $attemptIds)
            ->join('questions', 'questions.id', '=', 'attempt_answers.question_id')
            ->join('skills', 'skills.id', '=', 'questions.skill_id')
            ->groupBy('skills.id', 'skills.title')
            ->havingRaw('count(*) >= 1')
            ->select(
                'skills.id', 'skills.title',
                DB::raw('sum(case when attempt_answers.is_correct then 1 else 0 end) as correct'),
                DB::raw('count(*) as total')
            )
            ->get()
            ->map(fn ($row) => [
                'skill' => $row->title,
                'accuracy_percent' => $row->total > 0 ? (int) round($row->correct / $row->total * 100) : 0,
                'attempts' => (int) $row->total,
            ])
            ->sortBy('accuracy_percent')
            ->take($limit)
            ->values()
            ->all();
    }

    public function schoolIndicators(School $school): array
    {
        $studentIds = Student::where('school_id', $school->id)->pluck('id');
        $teacherIds = Teacher::where('school_id', $school->id)->pluck('id');
        $classIds = SchoolClass::where('school_id', $school->id)->pluck('id');

        $attemptIds = Attempt::whereIn('student_id', $studentIds)->pluck('id');
        $activityIds = Activity::whereIn('teacher_id', $teacherIds)->pluck('id');

        return [
            'total_students' => $studentIds->count(),
            'total_teachers' => $teacherIds->count(),
            'active_students' => User::whereHas('student', fn ($q) => $q->whereIn('id', $studentIds))->where('status', 'active')->count(),
            'active_teachers' => User::whereHas('teacher', fn ($q) => $q->whereIn('id', $teacherIds))->where('status', 'active')->count(),
            'classes_count' => $classIds->count(),
            'activities_applied' => $activityIds->count(),
            'missions_completed' => Attempt::whereIn('id', $attemptIds)->where('status', 'completed')->count(),
            'participation_rate' => $this->participationRate($studentIds, $attemptIds),
            'completion_rate' => $this->completionRate($attemptIds),
            'critical_skills' => $this->criticalSkills($attemptIds),
        ] + $this->attemptStats($attemptIds);
    }

    public function networkIndicators(): array
    {
        $schools = School::where('status', 'active')->get();
        $attemptIds = Attempt::pluck('id');
        $studentIds = Student::pluck('id');

        return [
            'schools_count' => $schools->count(),
            'total_students' => $studentIds->count(),
            'total_teachers' => Teacher::count(),
            'missions_completed' => Attempt::where('status', 'completed')->count(),
            'campaigns_used' => Activity::distinct('campaign_id')->count('campaign_id'),
            'participation_rate' => $this->participationRate($studentIds, $attemptIds),
            'completion_rate' => $this->completionRate($attemptIds),
            'critical_skills' => $this->criticalSkills($attemptIds),
            'schools_breakdown' => $schools->map(fn (School $school) => [
                'id' => $school->id,
                'name' => $school->name,
                'students_count' => Student::where('school_id', $school->id)->count(),
            ])->values()->all(),
        ] + $this->attemptStats($attemptIds);
    }

    private function participationRate(Collection $studentIds, Collection $attemptIds): int
    {
        if ($studentIds->isEmpty()) {
            return 0;
        }

        $studentsWithAttempts = Attempt::whereIn('id', $attemptIds)->distinct('student_id')->count('student_id');

        return (int) round($studentsWithAttempts / $studentIds->count() * 100);
    }

    private function completionRate(Collection $attemptIds): int
    {
        if ($attemptIds->isEmpty()) {
            return 0;
        }

        $completed = Attempt::whereIn('id', $attemptIds)->where('status', 'completed')->count();

        return (int) round($completed / $attemptIds->count() * 100);
    }
}
