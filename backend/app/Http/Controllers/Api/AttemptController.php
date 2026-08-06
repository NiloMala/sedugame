<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\AttemptHint;
use App\Models\Hint;
use App\Models\Mission;
use App\Models\Question;
use App\Services\ProgressionService;
use App\Services\ScoringService;
use Illuminate\Http\Request;

class AttemptController extends Controller
{
    /**
     * POST /api/attempts
     */
    public function store(Request $request)
    {
        $student = $request->user()->student()->first(); // ->first() bypassa cache de relação
        abort_unless($student, 403, 'Apenas alunos podem iniciar missões.');

        $data = $request->validate([
            'mission_id' => ['required', 'exists:missions,id'],
            'activity_id' => ['nullable', 'exists:activities,id'],
        ]);

        $mission = Mission::findOrFail($data['mission_id']);

        $attempt = Attempt::create([
            'student_id' => $student->id,
            'activity_id' => $data['activity_id'] ?? null,
            'campaign_id' => $mission->campaign_id,
            'mission_id' => $mission->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        return response()->json(['data' => [
            'id' => $attempt->id,
            'status' => $attempt->status,
            'started_at' => $attempt->started_at,
        ]], 201);
    }

    /**
     * GET /api/attempts/{attempt}/next-question
     */
    public function nextQuestion(Request $request, Attempt $attempt)
    {
        $this->authorizeOwnership($request, $attempt);

        $answeredIds = $attempt->answers()->pluck('question_id');

        $question = Question::query()
            ->join('mission_stages', 'mission_stages.id', '=', 'questions.mission_stage_id')
            ->where('mission_stages.mission_id', $attempt->mission_id)
            ->whereNotIn('questions.id', $answeredIds)
            ->orderBy('mission_stages.order')
            ->orderBy('questions.id')
            ->select('questions.*')
            ->with(['options' => fn ($q) => $q->orderBy('order'), 'hints' => fn ($q) => $q->orderBy('order'), 'stage.location', 'stage.media'])
            ->first();

        abort_if(! $question, 404, 'no_more_questions');

        return ['data' => $this->presentQuestion($question)];
    }

    /**
     * POST /api/attempts/{attempt}/answers
     */
    public function answers(Request $request, Attempt $attempt, ScoringService $scoring)
    {
        $this->authorizeOwnership($request, $attempt);

        $data = $request->validate([
            'question_id' => ['required', 'exists:questions,id'],
            'time_spent_seconds' => ['required', 'integer', 'min:0'],
            'selected_option_id' => ['nullable', 'integer'],
            'selected_option_ids' => ['nullable', 'array'],
            'answer_text' => ['nullable'],
            'ordered_option_ids' => ['nullable', 'array'],
            'matches' => ['nullable', 'array'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $question = Question::with(['options', 'location'])->findOrFail($data['question_id']);

        abort_if(
            $attempt->answers()->where('question_id', $question->id)->exists(),
            422,
            'Esta questão já foi respondida nesta tentativa.'
        );

        $hintStats = AttemptHint::where('attempt_hints.attempt_id', $attempt->id)
            ->where('attempt_hints.question_id', $question->id)
            ->join('hints', 'hints.id', '=', 'attempt_hints.hint_id')
            ->selectRaw('count(*) as used, coalesce(sum(hints.score_penalty), 0) as penalty')
            ->first();

        $streak = $this->currentStreak($attempt);

        $result = $scoring->score($question, $data, (int) $hintStats->penalty, (int) $data['time_spent_seconds'], $streak);

        $answerText = $data['answer_text'] ?? null;

        $attempt->answers()->create([
            'question_id' => $question->id,
            'answer_text' => is_array($answerText) ? json_encode($answerText) : $answerText,
            'selected_option_id' => $data['selected_option_id'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'distance_meters' => $result['distance_meters'],
            'is_correct' => $result['is_correct'],
            'score' => $result['score'],
            'time_spent_seconds' => $data['time_spent_seconds'],
            'hints_used' => (int) $hintStats->used,
        ]);

        $attempt->increment('score', $result['score']);
        $attempt->increment('time_spent_seconds', $data['time_spent_seconds']);

        return ['data' => [
            'is_correct' => $result['is_correct'],
            'score' => $result['score'],
            'distance_meters' => $result['distance_meters'],
            'explanation' => $question->explanation,
            'correct_option_id' => in_array($question->type, ['single_choice', 'true_false'], true)
                ? $question->options->firstWhere('is_correct', true)?->id
                : null,
        ]];
    }

    /**
     * POST /api/attempts/{attempt}/hints/{hint}
     */
    public function hint(Request $request, Attempt $attempt, Hint $hint)
    {
        $this->authorizeOwnership($request, $attempt);

        $question = $hint->question()->with('stage')->first();
        abort_if(! $question || $question->stage?->mission_id !== $attempt->mission_id, 404);

        AttemptHint::firstOrCreate(
            ['attempt_id' => $attempt->id, 'hint_id' => $hint->id],
            ['question_id' => $question->id, 'used_at' => now()]
        );

        return ['data' => ['content' => $hint->content]];
    }

    /**
     * POST /api/attempts/{attempt}/complete
     */
    public function complete(Request $request, Attempt $attempt, ProgressionService $progression)
    {
        $this->authorizeOwnership($request, $attempt);

        abort_if($attempt->status === 'completed', 422, 'Esta tentativa já foi concluída.');

        $attempt->update(['status' => 'completed', 'completed_at' => now()]);

        $result = $progression->applyCompletion($attempt);

        return ['data' => [
            'score' => $result['score'],
            'experience_gained' => $result['experience_gained'],
            'level_up' => $result['level_up'],
            'achievements_unlocked' => $result['achievements_unlocked']->map(fn ($achievement) => [
                'id' => $achievement->id,
                'title' => $achievement->title,
                'icon' => $achievement->icon,
            ])->values(),
            'collectibles_unlocked' => $result['collectibles_unlocked']->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'category' => $item->category,
                'icon' => $item->icon,
                'image_url' => $item->image_url,
                'rarity' => $item->rarity,
            ])->values(),
        ]];
    }

    private function authorizeOwnership(Request $request, Attempt $attempt): void
    {
        $student = $request->user()->student()->first(); // ->first() bypassa cache de relação
        abort_if(! $student || $attempt->student_id !== $student->id, 403, 'Esta tentativa não pertence a você.');
    }

    private function currentStreak(Attempt $attempt): int
    {
        $streak = 0;
        foreach ($attempt->answers()->orderByDesc('id')->pluck('is_correct') as $wasCorrect) {
            if (! $wasCorrect) {
                break;
            }
            $streak++;
        }

        return $streak;
    }

    private function presentQuestion(Question $question): array
    {
        return [
            'id' => $question->id,
            'statement' => $question->statement,
            'type' => $question->type,
            'time_limit_seconds' => $question->time_limit_seconds,
            'blanks_count' => $question->type === 'fill_blank'
                ? $question->options->pluck('order')->unique()->count()
                : null,
            'options' => $question->options->map(fn ($option) => [
                'id' => $option->id,
                'text' => $option->text,
                'image_url' => $option->image_url,
                'pair_side' => $option->side,
            ])->values(),
            'hints' => $question->hints->map(fn ($hint) => ['id' => $hint->id])->values(),
            'stage' => $question->stage ? [
                'id' => $question->stage->id,
                'order' => $question->stage->order,
                'content' => $question->stage->content,
                'location' => $question->stage->location ? [
                    'id' => $question->stage->location->id,
                    'name' => $question->stage->location->name,
                    'latitude' => (float) $question->stage->location->latitude,
                    'longitude' => (float) $question->stage->location->longitude,
                ] : null,
                'media' => $question->stage->media->map(fn ($media) => [
                    'id' => $media->id,
                    'type' => $media->type,
                    'file_url' => $media->file_url,
                ])->values(),
            ] : null,
        ];
    }
}
