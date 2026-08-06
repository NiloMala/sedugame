<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Concerns\FormatsPagination;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    use FormatsPagination;

    public function index(Request $request)
    {
        $teacher = $request->user()->teacher()->first();
        abort_unless($teacher, 403);

        $query = $teacher->activities()->with('campaign')->withCount('attempts')->orderByDesc('id');

        return $this->paginated($query->paginate(20), fn (Activity $activity) => [
            'id' => $activity->id,
            'title' => $activity->title ?? $activity->campaign->title,
            'campaign' => ['id' => $activity->campaign->id, 'title' => $activity->campaign->title],
            'starts_at' => $activity->starts_at,
            'ends_at' => $activity->ends_at,
            'attempt_limit' => $activity->attempt_limit,
            'ranking_enabled' => $activity->ranking_enabled,
            'status' => $activity->status,
            'attempts_count' => $activity->attempts_count,
        ]);
    }

    /**
     * POST /api/teacher/activities
     */
    public function store(Request $request)
    {
        $teacher = $request->user()->teacher()->first();
        abort_unless($teacher, 403);

        $data = $request->validate([
            'campaign_id' => ['required', 'exists:campaigns,id'],
            'class_ids' => ['required', 'array', 'min:1'],
            'class_ids.*' => ['exists:classes,id'],
            'title' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'attempt_limit' => ['nullable', 'integer', 'min:1'],
            'ranking_enabled' => ['sometimes', 'boolean'],
        ]);

        $ownClassIds = $teacher->classes()->pluck('classes.id')->unique();
        $foreignClassIds = collect($data['class_ids'])->diff($ownClassIds);
        abort_if($foreignClassIds->isNotEmpty(), 403, 'Você só pode atribuir atividades às suas próprias turmas.');

        $activity = Activity::create([
            'teacher_id' => $teacher->id,
            'campaign_id' => $data['campaign_id'],
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'attempt_limit' => $data['attempt_limit'] ?? null,
            'ranking_enabled' => $data['ranking_enabled'] ?? false,
            'status' => 'active',
        ]);
        $activity->classes()->sync($data['class_ids']);

        return response()->json(['data' => $activity->load('campaign', 'classes')], 201);
    }

    /**
     * GET /api/teacher/activities/{activity}/results
     */
    public function results(Request $request, Activity $activity, ReportService $reports)
    {
        $teacher = $request->user()->teacher()->first();
        abort_unless($teacher && $activity->teacher_id === $teacher->id, 403);

        $attempts = $activity->attempts()->with('student.user')->get();
        $attemptIds = $attempts->pluck('id');

        $students = $attempts->map(fn ($attempt) => [
            'student_id' => $attempt->student_id,
            'name' => $attempt->student->user->name,
            'status' => $attempt->status,
            'score' => $attempt->score,
            'time_spent_seconds' => $attempt->time_spent_seconds,
        ])->values();

        return ['data' => $reports->attemptStats($attemptIds) + [
            'critical_skills' => $reports->criticalSkills($attemptIds),
            'students' => $students,
        ]];
    }
}
